
var gulp = require('gulp');
var watch = require('gulp-watch');
var shell = require('gulp-shell');

gulp.task('api', function () {
    shell.task([
        'vendor/bin/docile docs'
    ]);
});

