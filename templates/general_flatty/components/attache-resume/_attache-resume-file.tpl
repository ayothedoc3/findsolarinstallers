<!-- attache resume tpl -->

<div class="submit-cell">
    <div class="field">
        <div class="file-input d-flex">
            <input type="hidden" name="resume_file" value="" />
            <input class="file w-100" id="attached_file_{$captcha_box_id}" type="file" name="resume" />
            <span class="file-choose">{$lang.choose}</span>
            <div class="flex-fill"><input autocomplete="off" type="text" id="fake_attached_file_{$captcha_box_id}" class="file-name w-100" placeholder="{$lang.attach_resume}" /></div>
        </div>
    </div>
</div>

{addJS file=$smarty.const.RL_LIBS_URL|cat:'jquery/upload/jquery.ui.widget.js'}
{addJS file=$smarty.const.RL_LIBS_URL|cat:'jquery/upload/jquery.iframe-transport.js'}
{addJS file=$smarty.const.RL_LIBS_URL|cat:'jquery/upload/jquery.fileupload.js'}
{addJS file=$smarty.const.RL_LIBS_URL|cat:'jquery/upload/jquery.fileupload-ui.js'}
{addJS file=$rlTplBase|cat:'components/attache-resume/_attache-resume.js'}

<!-- attache resume tpl end -->
