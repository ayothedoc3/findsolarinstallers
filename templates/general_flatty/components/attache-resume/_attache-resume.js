
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.10.1
 *  LICENSE: FL1KU6QLIUJA - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: bizlisting.xyz
 *  FILE: _ATTACHE-RESUME.JS
 *  
 *  The software is a commercial product delivered under single, non-exclusive,
 *  non-transferable license for one domain or IP address. Therefore distribution,
 *  sale or transfer of the file in whole or in part without permission of Flynax
 *  respective owners is considered to be illegal and breach of Flynax License End
 *  User Agreement.
 *  
 *  You are not allowed to remove this information from the file without permission
 *  of Flynax respective owners.
 *  
 *  Flynax Classifieds Software 2026 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

$(function(){
    "use strict";

    $('input[name=resume]').closest('form').each(function(){
        var self = this;
        var required_fields = ['contact_name', 'contact_email', 'contact_message', 'security_code'];
        var allow_submit = false;
        var $button = $(this).find('input[name=finish]');

        // File upload handler
        $(this).find('input[name=resume]').fileupload({
            url: rlConfig['ajax_url'] + '?mode=attachResume',
            dataType: 'json',
            autoUpload: true,
            singleFileUploads: true,
            add: function(e, data) {
                $(self).find('.file-name').val(data.files[0].name).removeClass('error');
                allow_submit = false;
                data.submit();
            },
            getFilesFromResponse: function(data) {
                if (data.result.status == 'OK') {
                    allow_submit = true;
                } else {
                    printMessage('error', lang['system_error']);
                }
            },
            fail: function(e, data) {
                printMessage('error', lang['system_error']);
            }
        });

        $(this).submit(function(e){
            var $fileInput = $(this).find('.file-name');
            var file_name = $fileInput.val();

            if (file_name) {
                var valid_ext = ['jpg', 'png', 'pdf', 'doc', 'docx', 'zip', 'rar'];
                var file_ext = file_name.substr(file_name.lastIndexOf('.') + 1);

                if ($.inArray(file_ext, valid_ext) == -1){
                    printMessage(
                        'error',
                        lang['invalid_file_extension'].replace('{ext}', '"' + valid_ext.join(', ') + '"'),
                        '#' + $(this).find('input.file-name').attr('id')
                    );
                    return false;
                } else {
                    $button.val(lang['loading']);
                }
            } else {
                if ($(this).closest('.popup').length) {
                    $fileInput.addClass('error');
                } else {
                    printMessage(
                        'error',
                        lang['notice_field_empty'].replace('{field}', lang['attach_resume']),
                        '#' + $fileInput.attr('id')
                    );
                }
                return false;
            }

            return allow_submit;
        });
    });
});
