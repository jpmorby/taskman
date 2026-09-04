@setup
    $project_name = "taskman";
    $working_dir = "/var/www/eb171a0a-65ac-40f1-bb18-5e06a148c113/";
    $deploy_user = "task_me_1";
    $public_html = "/var/www/eb171a0a-65ac-40f1-bb18-5e06a148c113/public_html";
    $repo = "https://github.com/jpmorby/taskman.git";
    ##################################################

    $dateflag = date('Y-m-d_H-i-s');
    $temp_dir = $working_dir . '/' . $project_name . '-' . 'envoy' . '.' . $dateflag;
@endsetup

@servers([
    'prod' => ["$deploy_user@web-47.fxrm.com"],
])

@story('deploy', ['on' => 'prod'])
    backup_db
    clone_repo
    setup_env
    build
    publish
    optimize
    {{-- restart-queues --}}
    backup_old_version
@endstory

@task('clone_repo')
    echo "Cloning ... "
    cd {{ $working_dir }}

    git clone --depth 1 {{ $repo }} {{ $temp_dir }}

    if [ "{{ $branch }}X" != "X" ]; then
    cd {{ $temp_dir }}
    git checkout {{ $branch }}
    fi
@endtask

@task('setup_env')
    cd {{ $working_dir }}
    cp {{ $public_html }}/.env {{ $temp_dir }}

    if [ -f "{{ $public_html }}/auth.json" ]; then
    cp {{ $public_html }}/auth.json {{ $temp_dir }}
    fi

    echo "Cloning complete"
@endtask


@task('build')
    echo "Building ..."
    cd {{ $temp_dir }}

    [ -x /usr/bin/install_nvm_and_node.sh ] && /usr/bin/install_nvm_and_node.sh
    export NVM_DIR="$HOME/.nvm"
    [ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"

    composer install --no-ansi --no-dev
    npm ci
    npm run build

    php artisan migrate --force
    php artisan optimize:clear

    echo "Build complete"
@endtask

@task('optimize')
# has to run after the directory is moved
# to avoid The "/var/www/laravel/taskman-envoy.2025-03-18_09-53-00/resources/views" directory does not exist.
# and │ View [dashboard] not found. │

    echo "Optimizing ..."
    cd {{ $public_html }}
    php artisan optimize
@endtask

@task('publish')
    echo "Publishing"
    cd {{ $working_dir }}
    mv {{ $public_html }} {{ $project_name }}-backup.{{ $dateflag }}
    mv {{ $temp_dir }} {{ $public_html }}
    echo "Publish Complete"
@endtask

@task('restart-queues', ['on' => 'workers'])
    {{-- Happens Post Publish --}}

    echo "Restarting Queues"
    cd {{ $public_html }}
    # php artisan queue:restart
    # overkill but a simple queue:restart doesn't reload the new code
    sudo /usr/bin/supervisorctl restart all
    echo "Restart Complete"
@endtask

@task('backup_old_version')
    echo "Doing Backup"
    cd {{ $working_dir }}
    mkdir -p backups
    tar cfz backups/{{ $project_name }}-{{ $dateflag }}.tgz {{ $project_name }}-backup.{{ $dateflag }}
    rm -rf {{ $project_name }}-backup.{{ $dateflag }}
    echo "Backup complete"
@endtask

@task('backup_db', ['on' => 'prod'])
    echo "Performing Database Backup"
    cd {{ $working_dir }}

    # Backups and the temporary credentials file must not be world readable.
    umask 077
    mkdir -p {{ $working_dir }}/backups

    env_file="{{ $public_html }}/.env"
    dump_file="{{ $working_dir }}/backups/{{ $project_name }}-{{ $dateflag }}.sql"

    # Read one KEY=value from the .env without eval/xargs, so values containing
    # spaces, '#' or quotes survive intact and never reach the shell as code.
    read_env_value() {
        sed -n "s/^[[:space:]]*$1[[:space:]]*=[[:space:]]*//p" "$env_file" \
            | tail -n 1 \
            | sed -e 's/[[:space:]]*$//' -e 's/^"\(.*\)"$/\1/' -e "s/^'\(.*\)'$/\1/"
    }

    db_host="$(read_env_value DB_HOST)"
    db_port="$(read_env_value DB_PORT)"
    db_database="$(read_env_value DB_DATABASE)"
    db_username="$(read_env_value DB_USERNAME)"
    db_password="$(read_env_value DB_PASSWORD)"

    if [ -z "$db_database" ]; then
        echo "DB Backup FAILED: no DB_DATABASE in $env_file"
        exit 1
    fi

    # The password goes into a 0600 file, never into argv where `ps` would show it.
    defaults_file="$(mktemp "{{ $working_dir }}/.taskman-backup-cnf.XXXXXX")"
    trap 'rm -f "$defaults_file"' EXIT HUP INT TERM
    chmod 600 "$defaults_file"

    printf '[client]\n' > "$defaults_file"
    printf 'user=%s\n' "$db_username" >> "$defaults_file"
    printf 'password=%s\n' "$db_password" >> "$defaults_file"
    if [ -n "$db_host" ]; then printf 'host=%s\n' "$db_host" >> "$defaults_file"; fi
    if [ -n "$db_port" ]; then printf 'port=%s\n' "$db_port" >> "$defaults_file"; fi

    if ! mariadb-dump --defaults-extra-file="$defaults_file" --single-transaction --quick "$db_database" > "$dump_file"; then
        echo "DB Backup FAILED - aborting deploy before migrations run"
        rm -f "$defaults_file" "$dump_file"
        exit 1
    fi

    rm -f "$defaults_file"
    trap - EXIT HUP INT TERM

    gzip -f "$dump_file"
    echo "DB Backup complete: $dump_file.gz"
@endtask


@finished
@endfinished
