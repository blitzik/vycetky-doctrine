module.exports = function (grunt) {

    require('matchdep').filterDev('grunt-*').forEach(grunt.loadNpmTasks);

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        concat: {
            options: {
                separator: ';'
            },
            dist: {
                src: [
                    'js/jquery.js',
                    'js/netteForms.js',
                    'js/nette.ajax.js',
                    'js/main.js',
                    'js/jquery-ui.js',
                    'js/jquery.ui.touch-punch.min.js',
                    'js/passwordValidation.js',
                    'js/placeholders.js',
                    'js/timeConverter.js',
                    'js/sliders.js'
                ],
                dest: 'js/mins/js.js'
            }
        },

        uglify: {
            build: {
                files: {
                    'js/mins/js.min.js': ['js/mins/js.js']
                }
            }
        },

        cssmin: {
            front: {
                files: {
                    'css/front.min.css': ['css/original/front.css', 'css/jquery-ui.css']
                }
            },
            login: {
                files: {
                    'css/user_front.min.css': ['css/original/user_front.css']
                }
            }
        },

        sass: {
            build: {
                files: {
                    'css/original/front.css': 'css/SCSS/front.scss',
                    'css/original/user_front.css': 'css/SCSS/user_front.scss'
                }
            }
        }
    });

    grunt.registerTask('default', ['sass', 'cssmin', 'concat', 'uglify']);
    grunt.registerTask('buildcss', ['sass', 'cssmin']);
    grunt.registerTask('buildjs', ['concat', 'uglify']);

};