$(function() {
    $(document).on('click', '.entity-extend-apply', function (e) {
        var el = $(this);
        var message = el.data('message');
        var doAction = function() {
            confirmUpdate.preventClose(function(){});

            var url = $(el).attr('href').substr(21);
            var progressbar = $('#progressbar').clone();
            progressbar
                .attr('id', 'confirmUpdateLoading')
                .css({'display':'block', 'margin': '0 auto'})
                .find('h3').remove();

            confirmUpdate.$content.parent().find('a.cancel').hide();
            confirmUpdate.$content.parent().find('a.close').hide();
            confirmUpdate.$content.parent().find('a.btn-danger').replaceWith(progressbar);

            $('#confirmUpdateLoading').show();
            window.location.href = url;
        };

        if (!_.isUndefined(Oro.BootstrapModal)) {
            var confirmUpdate = new Oro.BootstrapModal({
                allowCancel: true,
                cancelText: _.__('Cancel'),
                title: 'Schema update confirmation',
                content: '<p>'+ _.__('Your config changes will be applied to schema.')+'</p></p>'+ _.__('It may take few minutes...')+'</p>',
                okText: 'Yes, Proceed'
            });
            confirmUpdate.on('ok', doAction);
            confirmUpdate.open();
        } else if (window.confirm(message)) {
            doAction();
        }

        return false;
    });
});
