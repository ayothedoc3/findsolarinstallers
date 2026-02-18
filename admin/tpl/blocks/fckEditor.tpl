<!-- build textarea with CKEditor -->

{if $fckEditorParams.fckEditorJsLoad}
    <script src="{$smarty.const.RL_LIBS_URL}ckeditor/ckeditor.js?rev={$config.static_files_revision}"></script>
    <script src="{$smarty.const.RL_LIBS_URL}ckfinder/ckfinder.js?rev={$config.static_files_revision}"></script>
{/if}

<textarea name="{$fckEditorParams.name}" id="{$fckEditorParams.name}" rows="10" cols="80">
    {$fckEditorParams.value}
</textarea>

{assign var='field_lang_code' value='_'|explode:$fckEditorParams.name|@array_reverse}

<script>
var toolbar = rlConfig['fckeditor_bar'] == 'Basic'
    ? 'Basic'
    : [
        ['Source', '-', 'Bold', 'Italic', 'Underline', 'Strike'],
        ['Image', 'Flash', 'Link', 'Unlink', 'Anchor'],
        ['Undo', 'Redo', '-', 'Find', 'Replace', '-', 'SelectAll', 'RemoveFormat'],
        ['TextColor', 'BGColor']
    ];

var editor_{$fckEditorParams.name} = CKEDITOR.replace('{$fckEditorParams.name}', {literal}{{/literal}
    language            : "{$field_lang_code.0}",
    width               : "{if $fckEditorParams.width == '100%' || !$fckEditorParams.width}100%{else}{$fckEditorParams.width}{/if}",
    height              : "{if $fckEditorParams.height}{$fckEditorParams.height}{else}160{/if}",
    toolbar             : toolbar,
    filebrowserBrowseUrl: rlConfig['libs_url'] + 'ckfinder/ckfinder.html',
    filebrowserUploadUrl: rlConfig['libs_url'] + 'ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files'
{literal}}{/literal});
CKFinder.setupCKEditor(editor_{$fckEditorParams.name}, '../');
</script>

<!-- build textarea with CKEditor end -->
