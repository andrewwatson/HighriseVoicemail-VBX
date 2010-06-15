<?php
$CI =& get_instance();
$highrise_user = PluginStore::get('highrise_user');
$currentlyIsUser = AppletInstance::getUserGroupPickerValue('dial-target') instanceof VBX_User; 
$dial_target = AppletInstance::getValue('dial-target', 'user-or-group');
?>
<style>
a.ajax_loader { background:url(<?php echo base_url() ?>assets/i/ajax-loader.gif); display:inline-block; width:16px; height:11px; vertical-align:middle; }
div.system_msg { display:inline-block; line-height:30px; vertical-align:center; }
div.system_msg > * { vertical-align:middle; }
div.vbx-applet div.section { margin-bottom:20px; }
span[class$="err"] { color:red; }
</style>

<div class="vbx-applet highrise_voicemail_applet">
    <?php if(empty($highrise_user)): ?>
    <div id="highrise_api_access" class="section">
        <h2>Highrise API Access</h2>
        <p>It looks like you are setting up for the first time. Please enter your access credentials so we can sync with Highrise.</p>

        <div class="vbx-input-container input" style="margin-bottom:10px;">
            <label>Highrise URL - The URL to your Highrise which is something like https or http://yoursite.highrisehq.com.</label>
            <input name="highrise_url" class="medium" type="text" value="" />
            <span class="highrise_url_err"></span>
        </div>

        <div class="vbx-input-container input" style="margin-bottom:10px;">
            <label>Token - Can be found under My Info in Highrise</label>
            <input name="highrise_token" class="medium" type="text" value="" />
            <span class="highrise_token_err"></span>
        </div>

        <div class="vbx-input-container input" style="margin-bottom:5px;">
            <label>Password - Your password used to login to Highrise</label>
            <input name="highrise_password" class="medium" type="password" value="" />
            <span class="highrise_password_err"></span>
        </div>

        <div style="line-height:30px;">
            <button class="inline-button submit-button highrise_test_creds_btn" style="margin-top:5px; vertical-align:center;">
                <span>Test</span>
            </button>
            <div class="system_msg"></div>
        </div>

        <div style="clear:both;"></div>
    </div>
    <?php endif; ?>

    <div class="prompt-for-group" style="display: <?php echo $currentlyIsUser ? "none" : ""  ?>">
        <h2>Prompt</h2>
        <p>What will the caller hear before leaving their message?</p>
        <?php echo AppletUI::AudioSpeechPicker('prompt') ?>
    </div>
    
    <div class="prompt-for-individual" style="display: <?php echo !$currentlyIsUser ? "none" : ""  ?>">
        <h2>Prompt</h2>
        
        <div class="vbx-full-pane">
            <fieldset class="vbx-input-container">
                The individual's personal voicemail greeting will be played.
            </fieldset>
        </div>
    </div>
    <br />

    <h2>Take voicemail</h2>
    <p>Which individual or group should receive the voicemail?</p>
    <?php echo AppletUI::UserGroupPicker('dial-target'); ?>
</div>

<script>
var base_url = '<?php echo base_url() ?>';
var highrise_user_data = <?php echo empty($highrise_user) ? 'false' : 'true' ?>;
</script>
