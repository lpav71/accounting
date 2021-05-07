/**
 * Settings for summernote editor.
 */

$(document).ready(function () {

    $(document).ready(function () {

        let editors = $('.editor-body');

        let configFull = {
            lang: 'ru-RU', // default: 'en-US'
            htmlMode: true,
            shortcuts: false,
            airMode: false,
            minHeight: 200, // set minimum height of editor
            maxHeight: null, // set maximum height of editor
            focus: false, // set focus to editable area after initializing summernote
            disableDragAndDrop: false,
            callbacks: {
                onImageUpload: function (files) {
                    uploadFile(files, $(this));
                },

                onMediaDelete: function (target) {

                    let fileURL = target[0].src;
                    deleteFile(fileURL);

                    // remove element in editor
                    target.remove();
                }
            }
        };

        // Featured editor
        editors.each(function () {
            $(this).summernote(configFull);
        });

        // Upload file on the server.
        function uploadFile(filesForm, editor) {
            let data = new FormData();

            // Add all files from form to array.
            for (let i = 0; i < filesForm.length; i++) {
                data.append("files[]", filesForm[i]);
            }

            $.ajax({
                data: data,
                type: "POST",
                url: "/ajax/uploader/upload",
                cache: false,
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                contentType: false,
                processData: false,
                success: function (images) {
                    //console.log(images);

                    // If not errors.
                    if (typeof images['error'] === 'object' && images['error'].length === 0) {

                        // Get all images and insert to editor.
                        for (let i = 0; i < images['url'].length; i++) {

                            editor.summernote('insertImage', images['url'][i], function ($image) {
                                //$image.css('width', $image.width() / 3);
                                //$image.attr('data-filename', 'retriever')
                            });
                        }
                    }
                    else {
                        // Get user's browser language.
                        let userLang = navigator.language;
                        let error = '';
                        if (userLang === 'ru-RU') {
                            error = 'Ошибка, не могу загрузить файл! Пожалуйста, проверьте файл или ссылку. ' +
                                'Изображение должно быть не менее 200px!';
                        } else {
                            error = 'Error, can\'t upload file! Please check file or URL. Image should be more then 200px!';
                        }

                        alert(error);
                    }
                }
            });
        }

        // Delete file from the server.
        function deleteFile(file) {
            let data = new FormData();
            data.append("file", file);
            $.ajax({
                data: data,
                type: "POST",
                url: "/ajax/uploader/delete",
                cache: false,
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                contentType: false,
                processData: false,
                success: function (image) {
                    //console.log(image);
                }
            });
        }

    });

});