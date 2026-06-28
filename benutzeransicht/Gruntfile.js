/* eslint-env node */

/**
 * Gruntfile for mod_benutzeransicht
 * Builds AMD JS from amd/src into amd/build
 */

module.exports = function(grunt) {
    'use strict';

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        eslint: {
            amd: {
                src: ['amd/src/*.js']
            }
        },

        uglify: {
            options: {
                compress: {
                    drop_console: false,
                    drop_debugger: true,
                    unused: true
                },
                mangle: {
                    reserved: ['require', 'define', 'M', 'Y']
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

    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-eslint');

    grunt.registerTask('lint', ['eslint:amd']);
    grunt.registerTask('build', ['eslint:amd', 'uglify:amd']);
    grunt.registerTask('default', ['build']);
    grunt.registerTask('dev', ['eslint:amd']);
};