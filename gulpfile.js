
var gulp = require('gulp');
var shell = require('gulp-shell');

gulp.task('api', shell.task([
    'vendor/bin/docile docs'
]));
gulp.task('watch', function () {
    gulp.watch(['./src/**/*.php'], ['api']);
});
gulp.task('default', ['api', 'watch']);

