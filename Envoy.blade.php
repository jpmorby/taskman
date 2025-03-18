@setup
    $project_name = "taskman";
    $repo = "https://github.com/jpmorby/taskman.git";
    ##################################################
    $working_dir = "/var/www/laravel/";
    $deploy_user = "www-data";
    $dateflag = date('Y-m-d_H-i-s');
    $temp_dir = $working_dir . '/' . $project_name . '-' . 'envoy' . '.' . $dateflag;
@endsetup

@servers([
    'prod' => ["$deploy_user@www-1.redmail.com"],
])

@story('deploy', ['on' => 'prod'])
    backup_db
    clone_repo
    setup_env
    build
    publish
    optimize
    restart-queues
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
    cp {{ $project_name }}/.env {{ $temp_dir }}

    if [ -f "{{ $project_name }}/auth.json" ]; then
    cp {{ $project_name }}/auth.json {{ $temp_dir }}
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
    cd {{ $project_name }} {{ $project_name }}
    php artisan optimize
@endtask

@task('publish')
    echo "Publishing"
    cd {{ $working_dir }}
    mv {{ $project_name }} {{ $project_name }}-backup.{{ $dateflag }}
    mv {{ $temp_dir }} {{ $project_name }}
    echo "Publish Complete"
@endtask

@task('restart-queues', ['on' => 'workers'])
    {{-- Happens Post Publish --}}

    echo "Restarting Queues"
    cd {{ $working_dir }}/{{ $project_name }}
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
    echo rm -rf {{ $project_name }}-backup.{{ $dateflag }}
    echo "Backup complete"
@endtask

@task('backup_db', ['on' => 'prod'])
    echo "Performing Database Backup"
    cd {{ $working_dir }}
    mkdir -p {{ $working_dir }}/backups
    export $(grep -v '^#' {{ $working_dir }}/{{ $project_name }}/.env | grep ^DB_ | xargs)
    echo mariadb-dump -u$DB_USERNAME -p$DB_PASSWORD \
    $DB_DATABASE > {{ $working_dir }}/backups/{{ $project_name }}-{{ $dateflag }}.sql
    gzip {{ $working_dir }}/backups/{{ $project_name }}-{{ $dateflag }}.sql
    echo "DB Backup complete"
@endtask


@finished
@endfinished
