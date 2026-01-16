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

    export NVM_DIR="$HOME/.nvm"
    [ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"

    {{-- composer install --no-dev --}}
    composer install --no-ansi
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
    mkdir -p {{ $working_dir }}/backups
    export $(grep -v '^#' {{ $public_html }}/.env | grep ^DB_ | xargs)
    echo mariadb-dump -u$DB_USERNAME -p$DB_PASSWORD \
    $DB_DATABASE > {{ $working_dir }}/backups/{{ $project_name }}-{{ $dateflag }}.sql
    gzip {{ $working_dir }}/backups/{{ $project_name }}-{{ $dateflag }}.sql
    echo "DB Backup complete"
@endtask


@finished
@endfinished
