module.exports = function (grunt) {

    require('matchdep').filterDev('grunt-*').forEach(grunt.loadNpmTasks);

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        concat: {
            jqueryui: {
                options: {
                    separator: ';'
                },

                src: [
                    'bower_components/jquery-ui/ui/core.js',
                    'bower_components/jquery-ui/ui/widget.js',
                    'bower_components/jquery-ui/ui/mouse.js',
                    'bower_components/jquery-ui/ui/position.js',
                    'bower_components/jquery-ui/ui/autocomplete.js',
                    'bower_components/jquery-ui/ui/menu.js',
                    'bower_components/jquery-ui/ui/slider.js'
                ],
                dest: 'assets/js/concatenated/reducedJqueryUi.js'
            },

            jqueryuiCss:{
                src: [
                    'bower_components/jquery-ui/themes/base/core.css',
                    'bower_components/jquery-ui/themes/base/autocomplete.css',
                    'bower_components/jquery-ui/themes/base/menu.css',
                    'bower_components/jquery-ui/themes/base/slider.css',
                    'bower_components/jquery-ui/themes/base/theme.css' // base theme
                    //'assets/css/jquery_ui_theme/jquery-ui.theme.css' // downloaded theme
                ],
                dest: 'assets/css/original/jqueryuiCss.css'
            },

            base: {
                options: {
                    separator: ';'
                },

                src: [
                    'bower_components/jquery/dist/jquery.js',
                    'bower_components/nette-forms/src/assets/netteForms.js',
                    'bower_components/nette.ajax.js/nette.ajax.js',
                    'assets/js/my_js/nette.ajax.spinner.extension.js',
                    'assets/js/concatenated/reducedJqueryUi.js',
                    'bower_components/jquery-ui-touch-punch/jquery.ui.touch-punch.js',
                    'bower_components/placeholders/dist/placeholders.jquery.js',
                    'assets/js/my_js/passwordValidation.js',
                    'assets/js/my_js/main.js'
                ],
                dest: 'assets/js/concatenated/js.js'
            },

            item: {
                options: {
                    separator: ';'
                },

                src: [
                    'assets/js/concatenated/reducedJqueryUi.js',
                    'assets/js/my_js/timeConverter.js',
                    'assets/js/my_js/sliders.js'
                ],
                dest: 'assets/js/concatenated/item.js'
            }
        },

        uglify: {
            front: {
                files: {
                    'assets/js/js.min.js': ['assets/js/concatenated/js.js']
                }
            },
            item: {
                files: {
                    'assets/js/item.min.js': ['assets/js/concatenated/item.js']
                }
            }
        },

        cssmin: {
            front: {
                files: {
                    'assets/css/front.min.css': ['assets/css/original/front.css', 'assets/css/jquery-ui.css']
                }
            },

            item:{
                files: {
                    'assets/css/item.min.css': ['assets/css/original/jqueryuiCss.css']
                }
            },

            user_front: {
                files: {
                    'assets/css/user_front.min.css': ['assets/css/original/user_front.css']
                }
            }
        },

        sass: {
            front: {
                files: {
                    'assets/css/original/front.css': 'assets/css/SCSS/front.scss'
                }
            },

            user_front: {
                files: {
                    'assets/css/original/user_front.css': 'assets/css/SCSS/user_front.scss'
                }
            }
        },

        copy: {
          main: {
            files: [
                {
                    expand: true,
                    flatten: true,
                    src: ['bower_components/jquery-ui/themes/base/images/*'],
                    dest: 'assets/css/images/'
                }
            ],
          }
        }
    });

    grunt.registerTask('default', ['sass', 'concat', 'cssmin', 'uglify', 'copy']);

    grunt.registerTask('build_front_css', ['sass:front', 'concat:jqueryuiCss', 'cssmin:front', 'cssmin:item']);

};