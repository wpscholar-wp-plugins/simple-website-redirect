'use strict';

let gulp = require( 'gulp' );
let shell = require( 'shelljs' );
let del = require( 'del' );

let config = {
	svn: {
		url: 'http://plugins.svn.wordpress.org/simple-website-redirect/',
		src: [
			'./**',
			'!**/screenshot-*.png',
			'!**/screenshot-*.jpg',
			'!**/screenshot-*.gif',
			'!**/icon-*.png',
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
			'!**/composer.*',
			'!**/vendor',
			'!**/vendor/**',
		],
		dest: './svn/trunk',
		clean: './svn/trunk/**/*'
	}
};

gulp.task( 'checkout', (done) => {
	shell.exec( 'svn co ' + config.svn.url + ' svn' );
	done();
} );

gulp.task( 'clean', () => {
	return del( config.svn.clean );
} );

gulp.task( 'copy', () => {
	return gulp.src( config.svn.src ).pipe( gulp.dest( config.svn.dest ) );
} );

gulp.task( 'stage', gulp.series( 'clean', 'copy' ) );

gulp.task( 'default', gulp.series( 'stage' ) );
