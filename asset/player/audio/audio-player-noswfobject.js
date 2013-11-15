var AudioPlayer = function () {
    var H = [];
    var D;
    var F = "";
    var A = {};
    var E = -1;
    var G = "9";

    function B(I) {
        if (document.all && !window[I]) {
            for (var J = 0; J < document.forms.length; J++) {
                if (document.forms[J][I]) {
                    return document.forms[J][I];
                    break
                }
            }
        }
        return document.all ? window[I] : document[I]
    }

    function C(I, J, K) {
        B(I).addListener(J, K)
    }

    return{setup:function (J, I) {
        F = J;
        A = I;
        if (swfobject.hasFlashPlayerVersion(G)) {
            swfobject.switchOffAutoHideShow();
            swfobject.createCSS("p.audioplayer_container span", "visibility:hidden;height:24px;overflow:hidden;padding:0;border:none;")
        }
    }, getPlayer:function (I) {
        return B(I)
    }, addListener:function (I, J, K) {
        C(I, J, K)
    }, embed:function (I, K) {
        var N = {};
        var L;
        var J = {};
        var O = {};
        var M = {};
        for (L in A) {
            N[L] = A[L]
        }
        for (L in K) {
            N[L] = K[L]
        }
        if (N.transparentpagebg == "yes") {
            J.bgcolor = "#FFFFFF";
            J.wmode = "transparent"
        } else {
            if (N.pagebg) {
                J.bgcolor = "#" + N.pagebg
            }
            J.wmode = "opaque"
        }
        J.menu = "false";
        for (L in N) {
            if (L == "pagebg" || L == "width" || L == "transparentpagebg") {
                continue
            }
            O[L] = N[L]
        }
        M.name = I;
        M.style = "outline: none";
        O.playerID = I;
        swfobject.embedSWF(F, I, N.width.toString(), "24", G, false, O, J, M);
        H.push(I)
    }, syncVolumes:function (I, K) {
        E = K;
        for (var J = 0; J < H.length; J++) {
            if (H[J] != I) {
                B(H[J]).setVolume(E)
            }
        }
    }, activate:function (I, J) {
        if (D && D != I) {
            B(D).close()
        }
        D = I
    }, load:function (K, I, L, J) {
        B(K).load(I, L, J)
    }, close:function (I) {
        B(I).close();
        if (I == D) {
            D = null
        }
    }, open:function (I, J) {
        if (J == undefined) {
            J = 1
        }
        B(I).open(J == undefined ? 0 : J - 1)
    }, getVolume:function (I) {
        return E
    }}
}();