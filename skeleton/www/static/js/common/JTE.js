/**
 * Js template engine
 *
 * @author    chenxin<chenxin619315@gmail.com>
 */
;String.prototype.inc=function(s){return this.indexOf(s)>-1?true:false;}
;String.prototype.trim = function() {return this.replace(/^\s+|\s+$/g, '');}
;String.prototype.clear_space = function() {return this.replace(/\s+/g, '');}
;String.prototype.clear_excess_space = function() {return this.trim().replace(/\s+/g, ' ');}
;String.prototype.clear_html_space = function(){return this.replace(/>\s+</g, '><');}
;function count_obj_keys(obj) {
    var count = 0;
    for ( var k in obj ) {
        if ( obj.hasOwnProperty(k) ) count++;
    }
    return count;
}
;String.prototype.utf8Bytes = function () {
    var bytes = 0; 
    for ( var i = 0 ; i < this.length; i++ ) {
        var u = this.charCodeAt(i);
        if ( u < 0x0000007F ) {
            bytes += 1;
        } else if ( u < 0x000007FF ) {
            bytes += 2;
        } else if ( u < 0x0000FFFF ) {
            bytes += 3;
        } else if ( u < 0x001FFFFF ) {
            bytes += 4;
        } else if ( u < 0x03FFFFFF ) {
            bytes += 5;
        } else {
            bytes += 6;
        }
    }
    return bytes; 
}
;String.prototype.utf8_bytes_substr = function(offset, bytes) {
    var l = 0, c, str = [];
    for ( var i = offset; i < this.length; i++ ) {
        c = this.charAt(i);
        l += c.utf8Bytes();
        if ( l > bytes ) break;
        str.push(c);
    }

    return str.join('');
}
;function std_json(ret)
{
    if ( typeof(ret) === 'string' ) {
        var json = null;
        try {json = eval('('+ret+')');}
        catch ( err ) {}
    } else {
        json = ret;
    }

    return json;
}
;function clear_duplicate(src, dst)
{
    for ( k in src ) {
        if ( ! src.hasOwnProperty(k) ) continue;
        if ( dst[k] && dst[k] == src[k] ) {
            delete src[k];
        }
    }
    return src;
}
;function object_extend(src, dst)
{
    for ( k in dst ) {
        if ( ! dst.hasOwnProperty(k) ) continue;
        if ( src[k] ) {
            src[k] = dst[k];
        }
    }
    return src;
}

var JTE    = function()
{
    var o = {};

    o.print_prefix    = '=';
    o._G        = {};
    o.fnPool    = {};


    //clear the global symtab
    o.clear        = function()
    {
        //for (k in this._G) {if (this._G.hasOwnProperty(k)) { delete this._G[k]; } }
        this._G    = {};
        return this;
    }

    //assign a variable to the template engine
    o.assign    = function(key, val)
    {
        this._G[key]    = val;
        return this;
    }

    /**
     * execute the template compiled code
     *         and get the final execute result
     *
     * @param    tpl    template string
     */
    o.execute    = function(tpl, name)
    {
        if ( name == undefined ) name = 'default';

        var fn_str = null;

        //check the cache pool first
        if ( this.fnPool[name] != undefined )
            fn_str = this.fnPool[name];
        else 
        {
            //code reguler
            var reg    = /<%=?([^>]+)%>/g;
            var CD    = "var C = [];\n";

            var append    = function(line)
            {
                var T = line.replace(/'/g, "\\\'").replace(/[\n\t\r]{1,}/g, " ");/*.replace(/\s{2,}/g, " ");*/
                CD += "C.push('"+T+"');\n";
            }

            var CUR    = 0;        //current position
            var CHR    = 0;
            var TMP    = null;
            var LGC    = null;
            while ( (m = reg.exec(tpl)) != null )
            {
                append(tpl.slice(CUR, m.index));

                //handler the code
                CHR    = tpl[m.index + 2];
                LGC    = m[1].replace(/\{([a-z0-9_\[\]\.]+)\}/gi, "__G__.$1");
                if ( CHR == this.print_prefix )    CD += "C.push("+LGC+");\n";
                else CD += LGC+"\n";

                //update the CUR
                CUR    = m.index + m[0].length;
            }

            append(tpl.substr(CUR, tpl.length - CUR));
            CD    += "return C.join('');\n";

            //return (new Function('__G__', CD))(this._G);
            fn_str    = CD;
            this.fnPool[name]    = CD;
        }

        var ret = (function(__G__){
            var r = '';
            try {r = eval('(function(){'+fn_str+'}())');} catch ( err ) {console.log(err)}
            return r;
        }(this._G));
        
        return ret;
    }

    o.getParam    = function( _name, source ) 
    {
        var pattern = new RegExp("(\\?|#|&)" + _name + "=([^&#]*)");
        var m = (source || window.location.href).match(pattern);
        return (!m?"":m[2]);
    }

    o.getTimeString    = function( o_time, c_time )
    {
        t         = c_time - o_time;
        if ( t < 0 ) 
        {
            var d = new Date();
            d.setTime(o_time*1000);
            return d.getFullYear()+'年'+(d.getMonth()+1)+'月'+d.getDate()+'日';
        }

        if ( t < 5 )        return '刚刚';                            //just now
        if ( t < 60 )        return t+'秒前';                        //under one minuts
        if ( t < 3600 )        return Math.floor(t/60)+'分钟前';        //under one hour
        if ( t < 86400 )    return Math.floor(t/3600)+'小时前';        //under one day
        if ( t < 2592000 )    return Math.floor(t/86400)+'天前';        //under one month

        var d = new Date();
        d.setTime(o_time*1000);
        if ( t < 31104000 )        //under one year
        {
            return (d.getMonth()+1)+'月'+d.getDate()+'日';
        }

        return d.getFullYear()+'年'+(d.getMonth()+1)+'月'+d.getDate()+'日';
    }

    return o;
}();
