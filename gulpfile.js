let gulp = require('gulp');
let shell = require('gulp-shell');
let del = require('del');

let config = {
    svn: {
        url: 'http://plugins.svn.wordpress.org/simple-website-redirect/',
        src: [
            './**',
            '!**/screenshot-*.png',
            '!**/screenshot-*.jpg',
            '!**/screenshot-*.gif',
            '!**/svn',
            '!**/svn/**',
            '!**/readme.md',
            '!**/package.json',
            '!**/package-lock.json',
            '!**/node_modules',
            '!**/node_modules/**',
            '!**/bower.json',
            '!**/bower_components',
            '!**/bower_components/**',
            '!**/gulpfile.js',
        ],
        dest: './svn/trunk',
        clean: './svn/trunk/**/*'
    }
};

gulp.task('svn:checkout', shell.task('svn co ' + config.svn.url + ' svn'));

gulp.task('svn:clean', function () {
    return del(config.svn.clean);
});

gulp.task('svn:copy', ['svn:clean'], function () {
    return gulp.src(config.svn.src).pipe(gulp.dest(config.svn.dest));
});

gulp.task('svn:stage', ['svn:copy']);
