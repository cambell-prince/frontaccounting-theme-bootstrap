var gulp = require('gulp');
var gutil = require('gulp-util');
var less = require('gulp-less');
var child_process = require('child_process');
var exec2 = require('child_process').exec;
var async = require('async');
var template = require('lodash.template');
var rename = require("gulp-rename");

var execute = function(command, options, callback) {
  if (options == undefined) {
    options = {};
  }
  command = template(command, options);
  if (!options.silent) {
    gutil.log(gutil.colors.green(command));
  }
  if (!options.dryRun) {
    exec2(command, function(err, stdout, stderr) {
      gutil.log(stdout);
      gutil.log(gutil.colors.yellow(stderr));
      callback(err);
    });
  } else {
    callback(null);
  }
};

var paths = {
  less: ['less/*.less', '!vendor/**'],
  reload: ['default.css', '*.php', 'views/*.html', '!vendor/**']
};

// livereload
var livereload = require('gulp-livereload');
var lr = require('tiny-lr');
var server = lr();

gulp.task('default', function() {
  // place code for your default task here
});

gulp.task('do-reload', function() {
  return gulp.src('../../index.php').pipe(livereload(server));
});

gulp.task('reload', function() {
  server.listen(35729, function(err) {
    if (err) {
      return console.log(err);
    }
    gulp.watch(paths.reload, [ 'do-reload' ]);
    gulp.watch(paths.less, [ 'less' ]);
  });
});

gulp.task('less', function () {
  gulp.src('./less/default.less')
    .pipe(less({
      paths: [ 'less', 'vendor/twbs/bootstrap/less' ]
    }))
    .pipe(gulp.dest('./'));
});

gulp.task('upload', function(cb) {
  var options = {
    dryRun: true,
    silent : false,
    src : "htdocs",
    dest : "root@saygoweb.com:/var/www/virtual/saygoweb.com/bms/htdocs/theme/bootstrap",
    key : "~/.ssh/dev_rsa"
  };
  execute(
    'rsync -rzlt --chmod=Dug=rwx,Fug=rw,o-rwx --delete --exclude-from="upload-exclude.txt" --stats --rsync-path="sudo -u vu2006 rsync" --rsh="ssh -i <%= key %>" <%= src %>/ <%= dest %>',
    options,
    cb
  );
});

gulp.task('tasks', function(cb) {
  var command = 'grep gulp\.task gulpfile.js';
  execute(command, null, function(err) {
    cb(null); // Swallow the error propagation so that gulp doesn't display a nodejs backtrace.
  });
});

gulp.task('watch', function() {
});
