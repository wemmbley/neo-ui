'use strict';

const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const browserSync = require('browser-sync');
const concat = require('gulp-concat');
const babel = require('gulp-babel');
const browserify = require('gulp-browserify');

gulp.task('serve', function() {
    browserSync.init({
        server: true,
        port: 3002,
        open: false
    });

    gulp.watch("neo/src/scss/**/*.scss")
        .on('change', gulp.series('sass'));

    gulp.watch("neo/src/js/**/*.js")
        .on('change', gulp.series('js'));

    gulp.watch("./*.html")
        .on('change', browserSync.reload);
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
        // .pipe(babel({
        //     "presets": [
        //         [
        //             "@babel/preset-env",
        //             {
        //                 "targets": {
        //                     "edge": "17",
        //                     "firefox": "60",
        //                     "chrome": "67",
        //                     "safari": "11.1"
        //                 },
        //                 "useBuiltIns": "usage",
        //                 "corejs": "3.6.5"
        //             }
        //         ]
        //     ]
        // }))
        .pipe(browserify())
        .pipe(browserSync.stream())
        .pipe(gulp.dest('./neo/dist/'));
});
gulp.task('copyFonts', () => {
    return gulp.src('./neo/src/fonts/**/*.*')
        .pipe(gulp.dest('./neo/dist/fonts'));
});


gulp.task('default', gulp.series('serve', 'sass', 'copyFonts'));