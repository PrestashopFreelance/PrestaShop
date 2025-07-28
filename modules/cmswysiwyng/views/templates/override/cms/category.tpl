{* {extends file='templates:cms/category'} *}
{extends file="file:`$smarty.const._PS_THEME_DIR_`templates/cms/category.tpl"}
{block name='page_content' append}
  {if isset($my_description) && $my_description}
    <div class="cms-category-extra-description module-cmswysiwyng-extra-description">
      {$my_description nofilter}
    </div>
  {/if}
{/block}