{**
 * Small Banner Template
 *}
<div id="smallbanner-{$hook_name}" class="smallbanner smallbanner-link-{$hook_name}" {if $hook_name == 'displaytop'}style="top: {$smallbanner_top}px; right: {$smallbanner_right}px;"{/if}>
    {if isset($smallbanner_link) && $smallbanner_link}
        <a href="{$smallbanner_link}" target="_blank">
    {/if}
    <img src="{$smallbanner_img}" alt="{l s='Banner' mod='smallbanner'}" class="img-fluid" style="max-width: {$smallbanner_width}px; width: 100%; height: auto;" />
    {if isset($smallbanner_link) && $smallbanner_link}
        </a>
    {/if}
</div>
