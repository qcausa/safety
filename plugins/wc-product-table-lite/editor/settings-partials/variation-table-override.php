<?php
$react_app_props = json_encode(
  array(
    "tableOptions" => wcpt_react_app_get_product_tables_select_options(),
    "categoryTerms" => wcpt_react_app_get_taxonomy_terms_with_children("product_cat"),
  ),
  JSON_HEX_QUOT | JSON_HEX_APOS
);

?>
<div class="wcpt-toggle-options" data-wcpt-anchor="variation_table_override">

  <div class="wcpt-editor-light-heading wcpt-toggle-label">
    Variation table override
    <?php wcpt_pro_badge(); ?>
    <?php echo wcpt_icon('chevron-down'); ?>
  </div>

  <div class="<?php wcpt_pro_cover(); ?>">

    <!-- override method -->
    <div class="wcpt-editor-row-option" wcpt-model-key="variation_table_override" wcpt-react-app="variation_override"
      wcpt-react-app-props='<?php echo $react_app_props; ?>'></div>
  </div>

</div>