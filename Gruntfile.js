/*global module:false*/
/*global require:false*/
/*jshint -W097*/
"use strict";

module.exports = function(grunt) {
 
    // load all grunt tasks
    require('matchdep').filterDev('grunt-*').forEach(grunt.loadNpmTasks);
 
    grunt.initConfig({
 
        // watch for changes and trigger compass, jshint, uglify and livereload
        watch: {                        
            compass: {
                files: ['compass/**/*.{scss,sass}'],
                tasks: ['compass', 'copy']
            },
            js: {
                files: '<%= jshint.all %>',
                tasks: ['jshint', 'uglify']
            },
            css: {
                files: ['assets/css/*.css'],
                tasks: ['cssmin']
            },
            sync: {
                files: [
                    'admin/', 
                    'admin/**', 
                    '!admin/assets/compass', 
                    '!admin/assets/compass/**', 
                    '!admin/assets/scss', 
                    '!admin/assets/scss/**',
                    'includes', 
                    'includes/**', 
                    'languages', 
                    'languages/**', 
                    'public', 
                    'public/**', 
                    '!public/assets/compass', 
                    '!public/assets/compass/**', 
                    '!public/assets/scss', 
                    '!public/assets/scss/**', 
                    'charitable.php'                    
                    // '**',
                    // '!.DS_Store',
                    // '!**/.DS_Store',
                    // '!.git*', 
                    // '!.jshintrc',
                    // '!.sass-cache',
                    // '!.sass-cache/**',
                    // '!node_modules', 
                    // '!node_modules/**', 
                    // '!Gruntfile.js', 
                    // '!package.json',                     
                    // '!README.md', 
                    // '!config.rb',                     
                    // '!assets/compass', 
                    // '!assets/compass/**', 
                    // '!assets/scss', 
                    // '!assets/scss/**',
                    // '!tests', 
                    // '!tests/**', 
                    // '!bin', 
                    // '!bin/**'
                ],
                tasks: ['sync:dist']
            }        
        },

        // Sync
        sync: {          
            dist: {
                files: [
                    // includes files within path
                    {
                        src: [  
                            'admin/', 
                            'admin/**', 
                            '!admin/assets/compass', 
                            '!admin/assets/compass/**', 
                            '!admin/assets/scss', 
                            '!admin/assets/scss/**',
                            'includes', 
                            'includes/**', 
                            'languages', 
                            'languages/**', 
                            'public', 
                            'public/**', 
                            '!public/assets/compass', 
                            '!public/assets/compass/**', 
                            '!public/assets/scss', 
                            '!public/assets/scss/**', 
                            'charitable.php'                            
                            // 'assets/css/**', 
                            // ''
                            // '**',
                            // '!.DS_Store',
                            // '!**/.DS_Store',
                            // '!.git*', 
                            // '!.jshintrc',
                            // '!.sass-cache',
                            // '!.sass-cache/**',                  
                            // '!node_modules', 
                            // '!node_modules/**', 
                            // '!Gruntfile.js', 
                            // '!package.json',                     
                            // '!README.md', 
                            // '!config.rb',                     
                            // '!assets/compass', 
                            // '!assets/compass/**', 
                            // '!assets/scss', 
                            // '!assets/scss/**',
                            // '!tests', 
                            // '!tests/**', 
                            // '!vendor', 
                            // '!vendor/**'                            
                        ], 
                        dest: '/Users/ericdaams/Dropbox/Development/Projects/WP/wp-content/plugins/charitable'
                    }
                ], 
                verbose: true
            }
        },
 
        // compass and scss
        compass: {
            dist: {
                options: {
                    config: 'config.rb',
                    force: true
                }
            }
        },

        // copy CSS from compass dir to public/admin dirs
        copy: {
            dist: {
                files: [
                    {
                        src: [ 'compass/css/charitable-admin-menu.css' ],
                        dest: '/Users/ericdaams/Dropbox/Development/Projects/WP/wp-content/src/charitable/admin/assets/css/charitable-admin-menu.css'
                    },
                    {
                        src: [ 'compass/css/charitable-admin.css' ],
                        dest: '/Users/ericdaams/Dropbox/Development/Projects/WP/wp-content/src/charitable/admin/assets/css/charitable-admin.css'
                    }, 
                    {
                        src: [ 'compass/css/charitable.css' ],
                        dest: '/Users/ericdaams/Dropbox/Development/Projects/WP/wp-content/src/charitable/public/assets/css/charitable.css'
                    }
                ]
            }
        },
 
        // javascript linting with jshint
        jshint: {
            options: {
                jshintrc: '.jshintrc',
                "force": true
            },
            all: [
                'Gruntfile.js'
            ]
        },        

        // uglify to concat, minify, and make source maps
        uglify: {
            dist: {
                files: {
                    'admin/assets/js/charitable-admin.min.js': 'admin/assets/js/charitable-admin.js'
                }
            }
        },

        // minify CSS
        cssmin: {
            minify: {
                files: {
                    'public/assets/css/charitable.min.css' : [ 
                        'public/assets/css/charitable.css'
                    ]
                }
            }
        },        

        // make POT file
        makepot: {
            target: {
                options: {
                    cwd: '',                        // Directory of files to internationalize.
                    domainPath: '/languages',       // Where to save the POT file.                    
                    mainFile: 'charitable.php',     // Main project file.
                    potFilename: 'charitable.pot',  // Name of the POT file.
                    type: 'wp-theme',               // Type of project (wp-plugin or wp-theme).
                    updateTimestamp: true           // Whether the POT-Creation-Date should be updated without other changes.
                }
            }
        }

    });

    // register task
    // grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.registerTask('default', ['watch']);
    grunt.registerTask('build', ['jshint', 'uglify', 'makepot']);
};