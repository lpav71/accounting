$(function () {
    window.templatePattern=$('[data-item="template-pattern"]');
    let currentTemplate = 100;
    let summernoteSettings={
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
    }
    $('[data-action="template-add"]').on('click', function () {
        $('#main-form').append(window.templatePattern.html().replace(/templates\[]/g,"templates["+currentTemplate+"]"));
        $('#main-form').last().find('.new-editor-body').summernote(summernoteSettings);
        currentTemplate=currentTemplate+1;
        $('#main-form').find('.unique-form').last().find('[data-action="template-delete"]').on('click', deleteTemplate);
        $('#main-form').find('.unique-form').last().find('.sms-radio').on('change', deleteSummernote);
    });
    
    $('[data-action="template-delete"]').on('click', deleteTemplate);
    $('.sms-radio').on('change', deleteSummernote);

    function deleteTemplate(){
        result = confirm("Удалить шаблон?");
        let $context = $(this);
        if(result){
        if($context.parent().attr('data-id')){
        $.ajax
            ({
                type: "POST",
                url: $context.attr('delete-url'),
                dataType: 'json',
                async: true,
                data: { id: $context.parent().attr('data-id')},
                success: function (response) {
                    $context.parent().parent().parent().remove();
                }
            })
        }else{
            $context.parent().parent().parent().remove();
        }
    }
    }

    function deleteSummernote(){
            $(this).parent().find('.editor-body').summernote('destroy');
            $(this).parent().find('.new-editor-body').summernote('destroy');

    }

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