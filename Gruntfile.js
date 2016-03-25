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
                dest: 'www/assets/js/concatenated/reducedJqueryUi.js'
            },

            jqueryuiCss:{
                src: [
                    'bower_components/jquery-ui/themes/base/core.css',
                    'bower_components/jquery-ui/themes/base/autocomplete.css',
                    'bower_components/jquery-ui/themes/base/menu.css',
                    'bower_components/jquery-ui/themes/base/slider.css',
                    //'bower_components/jquery-ui/themes/base/theme.css' // base theme
                    'www/assets/css/jquery_ui_theme/jquery-ui.theme.css' // downloaded theme
                ],
                dest: 'www/assets/css/original/jqueryuiCss.css'
            },

            item_setting: {
                options: {
                    separator: ';'
                },

                src: [
                    // jqueryUi is defined in "base"
                    'www/assets/js/my_js/timeConverter.js',
                    'www/assets/js/my_js/sliders.js',
                    'www/assets/js/my_js/item.js'
                ],
                dest: 'www/assets/js/concatenated/item_setting.js'
            },

            base: {
                options: {
                    separator: ';'
                },

                src: [
                    'bower_components/jquery/dist/jquery.js',
                    'bower_components/nette-forms/src/assets/netteForms.js',
                    'bower_components/nette.ajax.js/nette.ajax.js',
                    'www/assets/js/my_js/nette.ajax.spinner.extension.js',
                    'www/assets/js/concatenated/reducedJqueryUi.js',
                    'bower_components/jquery-ui-touch-punch/jquery.ui.touch-punch.js',
                    'bower_components/placeholders/dist/placeholders.jquery.js',
                    'www/assets/js/my_js/passwordValidation.js',
                    'www/assets/js/my_js/listingItemsTables.js',
                    'www/assets/js/my_js/listingDelete.js',
                    'www/assets/js/my_js/main.js'
                ],
                dest: 'www/assets/js/concatenated/js.js'
            }
        },

        uglify: {
            front: {
                files: {
                    'www/assets/js/js.min.js': ['www/assets/js/concatenated/js.js']
                }
            },

            item_setting: {
                files: {
                    'www/assets/js/item_setting.min.js': ['www/assets/js/concatenated/item_setting.js']
                }
            }
        },

        cssmin: {
            front: {
                files: {
                    'www/assets/css/front.min.css': [
                        'www/assets/css/original/front.css',
                        'www/assets/css/original/jqueryuiCss.css'
                    ]
                }
            },

            user_front: {
                files: {
                    'www/assets/css/user_front.min.css': ['www/assets/css/original/user_front.css']
                }
            }
        },

        sass: {
            front: {
                files: {
                    'www/assets/css/original/front.css': 'www/assets/css/SCSS/front.scss'
                }
            },

            user_front: {
                files: {
                    'www/assets/css/original/user_front.css': 'www/assets/css/SCSS/user_front.scss'
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
                        dest: 'www/assets/css/images/'
                    }
                ]
            },

            font_awesome: {
                files: [
                    {
                        expand: true,
                        flatten: true,
                        src: ['bower_components/font-awesome-sass/assets/fonts/font-awesome/*'],
                        dest: 'www/assets/fonts/font-awesome/'
                    }
                ]
            }
        }
    });

    grunt.registerTask('default', ['copy', 'sass', 'concat', 'cssmin', 'uglify']);

    grunt.registerTask('copy_files', ['copy']);

    grunt.registerTask('build_js', ['concat:jqueryui', 'concat:base', 'concat:item_setting', 'uglify']);

    grunt.registerTask('build_front_css', ['sass:front', 'concat:jqueryuiCss', 'cssmin:front']);

};