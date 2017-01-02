<?php

require 'recipe/common.php';

set('keep_releases', 10);

server('smartclick-master', 'smartclick-master.smartclick.io')
    ->user('srvadmin')
    ->identityFile('~/.ssh/onur_adjo_rsa.pub', '~/.ssh/onur_adjo_rsa')
    ->stage('production')
    ->env('deploy_path', '/home/srvadmin/sites/sql-view')
    ->env('branch', 'master');


set('repository', 'git@github.com:onesvat/sql-view.git');

// Setup environment
task('environment', function () {
    run('cp /home/srvadmin/sites/sql-view/shared/.env {{release_path}}/.env');
})->desc('Setup environment');

// Reload php7.0-fpm
task('reload:php-fpm', function () {
    run('sudo /usr/sbin/service php7.0-fpm reload');
})->desc('Reload php7.0-fpm');

// Deploy!
task('deploy', [
    'deploy:prepare',
    'deploy:release',
    'deploy:update_code',
    'deploy:vendors',
    'environment',
    'deploy:symlink',
    'cleanup',
    'reload:php-fpm'
])->desc('Deploy');

after('deploy', 'success');
