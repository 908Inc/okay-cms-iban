{if $error}
    <div class="message_error">{$error|escape}</div>
{/if}

{if $action}
    <form method="POST" action="{$action|escape}" accept-charset="UTF-8">
        {foreach $fields as $name => $value}
            <input type="hidden" name="{$name|escape}" value="{$value|escape}">
        {/foreach}

        <button type="submit" class="button">{$button_text|escape}</button>
    </form>
{/if}
