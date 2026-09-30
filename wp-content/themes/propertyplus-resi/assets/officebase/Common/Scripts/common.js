
function mobileCheck() {
    //let check = false;
    //(function (a) { if (/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(a) || /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0, 4))) check = true; })(navigator.userAgent || navigator.vendor || window.opera);
    //return check;
    return screen.width <= 768;
};

/**
 * Add a stylesheet rule to the document (it may be better practice
 * to dynamically change classes, so style information can be kept in
 * genuine stylesheets and avoid adding extra elements to the DOM).
 * Note that an array is needed for declarations and rules since ECMAScript does
 * not guarantee a predictable object iteration order, and since CSS is 
 * order-dependent.
 * @param {Array} rules Accepts an array of JSON-encoded declarations
 * @example
addStylesheetRules([
  ['h2', // Also accepts a second argument as an array of arrays instead
    ['color', 'red'],
    ['background-color', 'green', true] // 'true' for !important rules 
  ], 
  ['.myClass', 
    ['background-color', 'yellow']
  ]
]);
*/
function addStylesheetRules(rules) {
    var styleEl = document.createElement('style');

    // Append <style> element to <head> in iframe HoaDon
    document.head.appendChild(styleEl);

    // Grab style element's sheet
    var styleSheet = styleEl.sheet;

    for (var i = 0; i < rules.length; i++) {
        var j = 1,
            rule = rules[i],
            selector = rule[0],
            propStr = '';
        // If the second argument of a rule is an array of arrays, correct our variables.
        if (Array.isArray(rule[1][0])) {
            rule = rule[1];
            j = 0;
        }

        for (var pl = rule.length; j < pl; j++) {
            var prop = rule[j];
            propStr += prop[0] + ': ' + prop[1] + (prop[2] ? ' !important' : '') + ';\n';
        }

        // Insert CSS Rule
        styleSheet.insertRule(selector + '{' + propStr + '}', styleSheet.cssRules.length);
    }
}

function showPhotos(data, index) {
    var fotoramaDiv = $('<div/>');
    var option = {
        allowfullscreen: true,
        hash: true,
        data: data,
        nav: "thumbs",
        thumbwidth: 100, // Chiều rộng thumbnails
        thumbheight: 80,  // Chiều cao thumbnails
        thumbmargin: 10
    };
    if (!mobileCheck()) {
        option.fit = 'none';
    }

    fotoramaDiv.fotorama(option)
        .on('fotorama:ready', function (e, fotorama) {
            if (index) {
                fotorama.show(index);
            }
        })
        .on('fotorama:load', function (e, fotorama) {
            fotorama.requestFullScreen();
        })
        .on('fotorama:fullscreenexit', function (e, fotorama) {
            fotorama.destroy();
            fotoramaDiv.remove();
            location.hash = '';
        });

    fotoramaDiv.appendTo('body');
}

function myCustomMap() {
   

}

function getCultureName() {
    return abp.utils.getCookieValue('.AspNetCore.Culture').split('c=')[2];
}



function onlyUniqueMarkers(marker, index, array) {

    var first = array.find(o => o.id === marker.id && o.type === marker.type);
    if (array.indexOf(first) !== index) {
        marker.setMap(null);
        return false;
    };

    return true;
}

function fitBounds(map, stringify) {

    if (stringify != null && stringify != "") {
        var obj = JSON.parse(stringify);
        const bounds = new window.google.maps.LatLngBounds();
        bounds.extend(new window.google.maps.LatLng(
            obj.BlLocationLat,
            obj.BlLocationLng
        ));
        bounds.extend(new window.google.maps.LatLng(
            obj.BrLocationLat,
            obj.BrLocationLng
        ));
        bounds.extend(new window.google.maps.LatLng(
            obj.TlLocationLat,
            obj.TlLocationLng
        ));
        bounds.extend(new window.google.maps.LatLng(
            obj.TrLocationLat,
            obj.TrLocationLng
        ));
        map.fitBounds(bounds);
    }
    else {
        map.setOptions({
            center: { lat: +$("input[name='center.lat']").val(), lng: +$("input[name='center.lng']").val() },
            zoom: +$("input[name='zoom']").val()
        });
    }

}


function iformat(icon) {

    var originalOption = icon.element;
    var dataIcon = $(originalOption).attr('image-url');

    if (!$(originalOption).attr("value") || !dataIcon)
        return $('<span>' + icon.text + '</span>');

    return $('<span class="select2-icon"><i class="ttcoban-ico" style="background-image:url(' + dataIcon + ')"></i> ' + icon.text + '</span>');
}

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

async function scrollToTop(ms) {
    let n = ms / 50;
    let range = window.scrollY;
    while (window.scrollY > 0) {
        window.scrollTo(0, window.scrollY - range / n);
        await sleep(50);
    }
}

$('.customCollape').on('click', function () {
    $(this).parent().parent().collapse('hide');
})

$('.m-popupmap').on('focusTarget', function () {
    let targetID = $(this).children('.object_wrapper').data('id');
    if (targetID)
        if ($(this).hasClass('show')) {
            $('.active-mobile').removeClass('active-mobile');
            $('#' + targetID).addClass('active-mobile');
        }
        else {
            $('#' + targetID).removeClass('active-mobile');
        }
});

$(document)
    .on('mouseenter', '.hover-apartment', function () {
        $("#" + $(this).data("id")).addClass('active-hover');
    })
    .on('mouseleave', '.hover-apartment', function () {
        $("#" + $(this).data("id")).removeClass('active-hover');
    })

    .on('mouseenter', '.hover-facility', function () {
        $("#" + $(this).data("id")).trigger('hover');
    })
    .on('mouseleave', '.hover-facility', function () {
        $("#" + $(this).data("id")).removeClass('open');
    })

    .on('mouseenter', '.hover-office', function () {
        $(".apartment-marker#" + $(this).attr("id")).addClass('active-hover');
    })
    .on('mouseleave', '.hover-office', function () {
        $(".apartment-marker#" + $(this).attr("id")).removeClass('active-hover');
    })

    .on('mouseenter', '.hover-project', function () {
        $(".office-marker#" + $(this).attr("id")).addClass('active-hover');
    })
    .on('mouseleave', '.hover-project', function () {
        $(".office-marker#" + $(this).attr("id")).removeClass('active-hover');
    });
/**
 * Notify custom
 * */

var ENotify = {
    Error: 0,
    Success: 1,
    Info: 2,
    Waring: 3,
    Redirect: 4
}

function showMessObject(obj = {}) {
    if ($.isNumeric(obj.code)) {

        if (obj.url) {
            var copyObj = Object.assign({}, obj);
            delete copyObj.url;

            localStorage.setItem('NotifyObject', JSON.stringify(copyObj));
            location.href = obj.url;
            return;
        }

        obj.isHtml = obj.isHtml ? obj.isHtml : false;

        switch (obj.code) {
            case ENotify.Info:
                abp.message.info(obj.message, obj.title, obj.isHtml);
                break;
            case ENotify.Success:
                abp.message.success(obj.message, obj.title, obj.isHtml);
                break;
            case ENotify.Waring:
                abp.message.warn(obj.message, obj.title, obj.isHtml);
                break;
            case ENotify.Error:
                abp.message.error(obj.message, obj.title, obj.isHtml);
                break;
        }
    }
    else
        abp.message.error("Default", "Default");
}

function showMessage(code = ENotify.Error, message = "Default", title = "Default", isHtml = false) {
    if ($.isNumeric(code)) {
        switch (code) {
            case ENotify.Info:
                abp.message.info(message, title, isHtml);
                break;
            case ENotify.Success:
                abp.message.success(message, title, isHtml);
                break;
            case ENotify.Waring:
                abp.message.warn(message, title, isHtml);
                break;
            case ENotify.Confirm:
                abp.message.confirm(message, title);
                break;
            //case ENotify.Error:
            //    abp.message.error(message, title, isHtml);
            //    break;
        }
    }
    else
        abp.message.error(message, title, isHtml);
}


/**
 * End Notify custom
 * */


function setCookie(cname, cvalue, exdays) {
    const d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    let expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function getCookie(cname) {
    let name = cname + "=";
    let decodedCookie = decodeURIComponent(document.cookie);
    let ca = decodedCookie.split(';');
    for (let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}

// (30/9/2026) Bo 2 handler abp.event.on("userAction"/"openUserProfile") o day - logic dang nhap .NET (Account/CheckLoggedIn) cua
// Angular Office/Resi goc, KHONG co trang nao trong ban WP goi abp.event.trigger; abp.js da duoc bo khoi theme (xem functions.php).
