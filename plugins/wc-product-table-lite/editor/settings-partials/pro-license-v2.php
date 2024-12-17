<div class="wcpt-toggle-options" data-wcpt-anchor="pro_license">
  <div class="wcpt-editor-light-heading wcpt-toggle-label">
    PRO License <?php echo wcpt_icon('chevron-down'); ?>
  </div>

  <!-- license key manager react app -->
  <div class="wcpt-editor-row-option" wcpt-model-key="pro_license_v2" wcpt-controller="pro_license_v2"
    wcpt-react-app="license_key_manager"
    wcpt-react-app-props='<?php echo json_encode(array("pluginServerUrl" => "https://pro.wcproducttable.com")); ?>'>
  </div>
  <div style="margin-left: 20px; margin-top: 10px;">
    <small>ℹ️ For troubleshooting see <a href="https://wcproducttable.notion.site/FAQs-f624e13d0d274a08ba176a98d6d79e1f"
        target="_blank">plugin FAQs</a> → PRO license.</small>
  </div>
</div>