module.exports = function (grunt) {

    require('matchdep').filterDev('grunt-*').forEach(grunt.loadNpmTasks);

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        concat: {
            options: {
                separator: ';'
            },
            base: {
                src: [
                    'js/jquery.js',
                    'js/netteForms.js',
                    'js/nette.ajax.js',
                    'js/jquery-ui.js',
                    'js/jquery.ui.touch-punch.min.js',
                    'js/placeholders.js',
                    'js/main.js'
                ],
                dest: 'js/mins/js.js'
            },
            item: {
                src: ['js/timeConverter.js', 'js/sliders.js'],
                dest: 'js/mins/item.js'
            }
        },

        uglify: {
            base: {
                files: {
                    'js/mins/js.min.js': ['js/mins/js.js']
                }
            },
            item: {
                files: {
                    'js/mins/item.min.js': ['js/mins/item.js']
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