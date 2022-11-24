{*
 * @author    PayU
 * @copyright Copyright (c) PayU
 *
 * http://www.payu.com
*}
{extends file='page.tpl'}

{block name='page_title'}
    {$payuOrderInfo}: <strong>{$total}</strong> {l s='(tax incl.)' mod='payu'}
    <img src="{$image}" id="payuLogo">
{/block}

{block name='page_content_container'}
    <section id="content" class="page-content page-cms">
        <div class="alert alert-warning">
            {$payuError}
        </div>
    </section>
{/block}
