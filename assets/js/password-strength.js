jQuery(function($){
    function updateStrength($input,$meter){
        var val = $input.val();
        var strength = wp.passwordStrength.meter(val, wp.passwordStrength.userInputBlacklist(), val);
        var cls = 'short';
        var text = pwsL10n.short;
        switch(strength){
            case 2:
                cls = 'bad';
                text = pwsL10n.bad;
                break;
            case 3:
                cls = 'good';
                text = pwsL10n.good;
                break;
            case 4:
                cls = 'strong';
                text = pwsL10n.strong;
                break;
        }
        $meter.removeClass('short bad good strong').addClass(cls).text(text);
    }

    $('.password-input input[type="password"]').each(function(){
        var $input = $(this);
        var $meter = $('<div class="password-strength"></div>');
        $input.after($meter);
        $input.on('keyup', function(){ updateStrength($input, $meter); });
    });
});
