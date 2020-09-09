
module.exports = function(grunt) {
    require('jit-grunt')(grunt);

    grunt.initConfig({
        uglify: {
            development: {
                options: {
                    compress: true,
                    preserveComments: false,
                    yuicompress: true,
                    optimization: 2
                },
                files: {
                    "Resources/Public/Javascript/VideoPlayer/VideoPlayer.min.js" : ['Resources/Public/Javascript/VideoPlayer/vendor/VideoFrame.min.js', 'Resources/Public/Javascript/VideoPlayer/videoplayerScripts.js']
                }
            }
        },
        watch: {
            js: {
                files: ['Resources/Public/Javascript/**/*.js'],
                tasks: ['uglify'],
                options: {
                    nospawn: true
                }
            }
        }
    });

    grunt.file.setBase('../')
    grunt.registerTask('default', ['uglify','watch']);
};
