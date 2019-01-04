'use strict';

module.exports = function (grunt) {

    // Load grunt tasks automatically
    require('load-grunt-tasks')(grunt);

    require('time-grunt')(grunt);

    grunt.loadNpmTasks('grunt-contrib-uglify');

    grunt.loadNpmTasks('grunt-contrib-watch');

    grunt.loadNpmTasks('grunt-contrib-cssmin');

    grunt.loadNpmTasks('grunt-beep');

    // Define the configuration for all the tasks
    grunt.initConfig({
        uglify: {
            my_target: {
                files: {
                    'Assets/build/media.min.js': ['Assets/js/libraries/*.js', 'Assets/js/*.js']
                }
            }
        },
        cssmin: {
            options: {
                mergeIntoShorthands: false,
                roundingPrecision: -1,
                sourceMap: true,
                root: 'Assets/build',
                sourceMapName: 'Assets/build/media.min.css.map'
            },
            target: {
                files: {
                    'Assets/build/media.min.css': ['Assets/css/libraries/*.css', 'Assets/css/*.css']
                }
            }
        },
        watch: {
            js: {
                files: ['Assets/js/libraries/*.js', 'Assets/js/*.js'],
                tasks: ['uglify', 'beep']
            },
            css: {
                files: ['Assets/css/libraries/*.css', 'Assets/css/*.css'],
                tasks: ['cssmin', 'beep']
            }
        }
    });

    grunt.registerTask('default', ['uglify', 'cssmin']);
};
