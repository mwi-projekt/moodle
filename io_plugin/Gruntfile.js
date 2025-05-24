/* eslint-env node */

/**
 * Gruntfile for mod_dhbwio
 * This file configures the build process for JavaScript and CSS files
 */

module.exports = function(grunt) {
    'use strict';

    // Project configuration
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        // JavaScript linting
        eslint: {
            amd: {
                src: ['amd/src/*.js']
            }
        },

        // JavaScript minification
        uglify: {
            options: {
                compress: {
                    drop_console: false, // Keep console for debugging
                    drop_debugger: true,
                    unused: true
                },
                mangle: {
                    reserved: ['require', 'define', 'M', 'Y'] // Preserve Moodle globals
                },
                output: {
                    comments: /^!/
                },
                sourceMap: true,
                sourceMapIncludeSources: true
            },
            amd: {
                files: [{
                    expand: true,
                    cwd: 'amd/src',
                    src: '*.js',
                    dest: 'amd/build',
                    ext: '.min.js'
                }]
            }
        },

        // Watch for changes
        watch: {
            amd: {
                files: ['amd/src/*.js'],
                tasks: ['build']
            },
            options: {
                spawn: false
            }
        }
    });

    // Load grunt plugins
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-eslint');

    // Register tasks
    grunt.registerTask('lint', ['eslint:amd']);
    grunt.registerTask('build', ['eslint:amd', 'uglify:amd']);
    grunt.registerTask('default', ['build']);

    // Development task (no minification)
    grunt.registerTask('dev', ['eslint:amd']);
};
