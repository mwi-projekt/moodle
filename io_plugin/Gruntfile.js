/**
 * Gruntfile for compiling mod_dhbwio AMD modules.
 *
 * This file configures tasks to be run by Grunt
 * https://gruntjs.com/ for the DHBW International Office Moodle module.
 *
 * @package    mod_dhbwio
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

module.exports = function(grunt) {
    // Load all grunt tasks.
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-eslint');
    grunt.loadNpmTasks('grunt-stylelint');

    // Project configuration.
    grunt.initConfig({
        eslint: {
            // Check JS files for errors and style issues.
            options: {
                overrideConfigFile: '.eslintrc'
            },
            amd: {
                src: ['amd/src/**/*.js']
            }
        },
        stylelint: {
            // Check CSS files for errors and style issues.
            css: {
                options: {
                    configFile: '.stylelintrc'
                },
                src: ['styles.css']
            }
        },
        uglify: {
            // Minify AMD JS modules.
            options: {
                sourceMap: true,
                mangle: false
            },
            amd: {
                files: [{
                    expand: true,
                    cwd: 'amd/src',
                    src: ['**/*.js'],
                    dest: 'amd/build',
                    ext: '.min.js'
                }]
            }
        },
        watch: {
            // Watch for changes to JS files and automatically rebuild.
            amd: {
                files: ['amd/src/**/*.js'],
                tasks: ['eslint:amd', 'uglify:amd']
            },
            css: {
                files: ['styles.css'],
                tasks: ['stylelint:css']
            }
        }
    });

    // Register tasks.
    grunt.registerTask('default', ['eslint', 'stylelint', 'uglify']);
    grunt.registerTask('amd', ['eslint:amd', 'uglify:amd']);
    grunt.registerTask('css', ['stylelint:css']);
};