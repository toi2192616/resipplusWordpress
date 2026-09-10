/* Here, there are some custom plug-ins.
 * Developed for ASP.NET Iteration Zero (http://aspnetzero.com). */
(function ($) {
    if (!$) {
        return;
    }

    /* A simple jQuery plug-in to make a button busy. */
    $.fn.buttonBusy = function (isBusy) {
        return $(this).each(function () {
            var $button = $(this);
            var $icon = $button.find('i');
            var $buttonInnerSpan = $button.find('span');

            if (isBusy) {
                if ($button.hasClass('button-busy')) {
                    return;
                }

                $button.attr('disabled', 'disabled');

                //change icon
                if ($icon.length) {
                    $button.data('iconOriginalClasses', $icon.attr('class'));
                    $icon.removeClass();
                    $icon.addClass('fa fa-spin fa-spinner');
                }

                //change text
                if ($buttonInnerSpan.length && $button.attr('busy-text')) {
                    $button.data('buttonOriginalText', $buttonInnerSpan.html());
                    $buttonInnerSpan.html($button.attr('busy-text'));
                }

                $button.addClass('button-busy');
            } else {
                if (!$button.hasClass('button-busy')) {
                    return;
                }

                //enable button
                $button.removeAttr('disabled');

                //restore icon
                if ($icon.length && $button.data('iconOriginalClasses')) {
                    $icon.removeClass();
                    $icon.addClass($button.data('iconOriginalClasses'));
                }

                //restore text
                if ($buttonInnerSpan.length && $button.data('buttonOriginalText')) {
                    $buttonInnerSpan.html($button.data('buttonOriginalText'));
                }

                $button.removeClass('button-busy');
            }
        });
    };

    $.fn.serializeFormToObject = function () {
        var object = {};
        $(this).serializeArray().forEach(o => {
            if (!object[o.name]) {
                object[o.name] = o.value;
            }
        });
        return object;
    };

    /**
     * Form chưa nhập gì?
     * */
    $.fn.isEmptyForm = function () {

        var array = $(this).serializeArray();
        for (var i = 0; i < array.length; i++) {
            if (array[i].value)
                return false;
        }

        return true;
    }
    /**
     * Cài lại sự kiện button next, prev, dots -> chặn direct link bởi thẻ cha <a/>
     * */
    $.fn.myfotorama = function (options = null) {

        options = options ? options : { loop: true };

        this.fotorama(options)
            .on('fotorama:ready', function (e, fotorama, extra) {

                $(this).children().addClass('fotorama__wrap--no-controls');

                //if ($(this).closest('.object_wrapper--horizontal').length === 0 && $(this).closest('.content-marker').length === 0) {
                //    var height = $(this).width() * 18 / 25;
                //    $(this).height(height);
                //}

                $(this).find('.fotorama__arr').click(function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $(this).hasClass('fotorama__arr--next') ? fotorama.show('>') : fotorama.show('<');
                })
                    .dblclick(function (e) {
                        e.stopPropagation();
                    });


                if (fotorama.activeFrame.$navDotFrame) {

                    var dots = $(this).find('.fotorama__nav__frame');
                    dots.click(function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        fotorama.show($(e.currentTarget).index() - 1);
                    });

                    dots.parent().click(function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                    });
                }
            });

        return this;
    }

    $.fn.orderSelect2 = function (options = {}) {

        $.extend(options, { minimumResultsForSearch: $(this.hasClass('enable-search-input')) ? 0 : -1 });

        this.select2(options)
            .on('select2:opening', function (e) {
                $('body').addClass('order-select--show');
            })
            .on('select2:closing', function () {
                if ($('.select2-container--open').length == 1)
                    $('body').removeClass('order-select--show');
            });

        return this;
    }

})(jQuery);