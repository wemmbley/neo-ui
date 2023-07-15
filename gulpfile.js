'use strict';

const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const browserSync = require('browser-sync');
const concat = require('gulp-concat');
const browserify = require('gulp-browserify');
const connectPHP = require('gulp-connect-php');
const request = require('request');

const PHP_HOST = '127.0.0.1';
const PHP_PORT = 8008;
const PHP_SERVER_URL = PHP_HOST + ':' + PHP_PORT + '/';

gulp.task('serve', function() {
    browserSync.init({
        server: true,
        port: 3002,
        open: false
    });

    connectPHP.server({
        base: 'neo/vendor/php',
        hostname: PHP_HOST,
        port: PHP_PORT,
        keepalive: true,
        debug: true,
    });

    gulp.watch("neo/src/scss/**/*.scss")
        .on('change', gulp.series('sass'));

    gulp.watch("neo/src/js/**/*.js")
        .on('change', gulp.series('js'));

    gulp.watch("neo/src/html/**/*.html")
        .on('change', gulp.series('compile'));
});
gulp.task('compile', function() {
    request.get(PHP_SERVER_URL + '?action=compile');

    return gulp.src('neo/src/html/**/*.html')
        .pipe(browserSync.stream())
        .pipe(gulp.dest('./neo/dist/'));
});
gulp.task('sass', function() {
    return gulp.src('./neo/src/scss/main.scss')
        .pipe(sass().on('error', sass.logError))
        .pipe(concat('main.css'))
        .pipe(browserSync.stream())
        .pipe(gulp.dest('./neo/dist/'));
});
gulp.task('js', () => {
    return gulp.src('./neo/src/js/main.js')
        .pipe(browserify())
        .pipe(browserSync.stream())
        .pipe(gulp.dest('./neo/dist/'));
});
gulp.task('copyFonts', () => {
    return gulp.src('./neo/src/fonts/**/*.*')
        .pipe(gulp.dest('./neo/dist/fonts'));
});


gulp.task('default', gulp.series('serve', 'sass', 'copyFonts'));