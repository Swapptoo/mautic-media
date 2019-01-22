// Logic for the provider type selector.
Mautic.mediaProvider = function () {

    mQuery('select[name="media[provider]"]:first').change(function () {
        var provider = mQuery(this).val(),
            $accountId = mQuery('input[name="media[account_id]"]:first'),
            $clientId = mQuery('input[name="media[client_id]"]:first'),
            $clientSecret = mQuery('input[name="media[client_secret]"]:first'),
            $token = mQuery('input[name="media[token]"]:first'),
            $refreshToken = mQuery('input[name="media[refresh_token]"]:first'),
            $callbackUri = mQuery('input#media-callback-uri'),
            callbackUri = $callbackUri.val();
        switch (provider) {
            case 'facebook':
                // Does not need a refresh token.
                $accountId.parent().removeClass('hide');
                $clientId.parent().removeClass('hide');
                $clientSecret.parent().removeClass('hide');
                $token.parent().removeClass('hide');
                $refreshToken.parent().addClass('hide');
                break;
            case 'google':
                // Does not need account
                $accountId.parent().addClass('hide');
                $clientId.parent().removeClass('hide');
                $clientSecret.parent().removeClass('hide');
                $token.parent().removeClass('hide');
                $refreshToken.parent().removeClass('hide');
                break;
            case 'snapchat':
                // Does not need account.
                $accountId.parent().addClass('hide');
                $clientId.parent().removeClass('hide');
                $clientSecret.parent().removeClass('hide');
                $token.parent().removeClass('hide');
                $refreshToken.parent().removeClass('hide');
                break;
            case 'bing':
                // Does not need account, or refresh token.
                $accountId.parent().addClass('hide');
                $clientId.parent().removeClass('hide');
                $clientSecret.parent().removeClass('hide');
                $token.parent().removeClass('hide');
                $refreshToken.parent().addClass('hide');
                break;
        }
        // Apply provider specific labels.
        $accountId.parent().find('label:first').text(Mautic.translate('mautic.media.form.provider.' + provider + '.account_id'));
        $clientId.parent().find('label:first').text(Mautic.translate('mautic.media.form.provider.' + provider + '.client_id'));
        $clientSecret.parent().find('label:first').text(Mautic.translate('mautic.media.form.provider.' + provider + '.client_secret'));
        $token.parent().find('label:first').text(Mautic.translate('mautic.media.form.provider.' + provider + '.token'));
        $refreshToken.parent().find('label:first').text(Mautic.translate('mautic.media.form.provider.' + provider + '.refresh_token'));

        // Update the callback URI.
        var parts = callbackUri.split('/');
        parts.pop();
        parts.push(provider);
        $callbackUri.val(parts.join('/'));
    }).trigger('change');
};
