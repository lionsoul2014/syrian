var Ajax = function() {
    var a = {};

    function createXMLHttp()
    {
        //Create a ajax instance
        var xmlhttp = null;

        //IE7,Mozilla,safari,firefox,Opare......
        if ( window.XMLHttpRequest ) xmlhttp = new XMLHttpRequest();

        //Old version IE
        else if ( window.ActiveXObject ) {
            var XMLArray = new Array('MSxml2.XMLHTTP.5.0', 'MSxml2.XMLHTTP.4.0',
                    'MSxml2.XMLHTTP.3.0', 'MSxml2.XMLHTTP', 'Microsoft.XMLHTTP');
            var success = false;
            for( i = 0;i < XMLArray.length && ! success; i++ ) {
                try{
                    xmlhttp = new ActiveXObject( XMLArray[i] );    
                    success = true;
                    break;
                } catch(e2) {}
            }

            if( ! success ) throw new Error('Fail To Create XMLHttpRequest Object');
        }

        return xmlhttp;
    }

    function statechange( xmlhttp, callback )
    {
        xmlhttp.onreadystatechange = function (){
            if ( xmlhttp.readyState == 4 ) {
                if ( xmlhttp.status == 200 ) {
                    if ( callback != undefined )   callback(xmlhttp.responseText);  
                } else {
                    throw new Error('Request Error');
                }
            }
        }
    }

    function _argumentsEncode(args)
    {
        if ( ! args ) return false;

        var param = [];
        for ( key in args )
        {
            if ( ! args.hasOwnProperty(key) ) continue;
            param.push(key+'='+args[key]);
        }

        return param.join('&');
    }

    a.get = function ( url, args, callback )
    {
        //create an ajax object
        var xmlhttp = createXMLHttp();
        statechange(xmlhttp, callback);

        var _param = _argumentsEncode(args);
        if ( _param != false )
        {
            if ( url.indexOf('?') != -1 ) url = url + '&' + _param;
            else url = url + '?' + _param;
        }

        xmlhttp.open('GET', url, true);
        xmlhttp.send();
    }

    a.post = function ( url, setting, callback )
    {
        //create an ajax abject
        var xmlhttp = createXMLHttp();
        xmlhttp.onreadystatechange = function (){
            if ( xmlhttp.readyState == 4 ) {
                if ( xmlhttp.status == 200 ) {
                    var data = xmlhttp.responseText;
                    if ( setting.dataType && setting.dataType === 'json' )
                    {
                        try {
                            data = eval('('+xmlhttp.responseText+')');
                        } catch (err) {
                            data = {};
                        }
                    }
                    callback && callback(data); 
                } else {
                    throw new Error('Request Error');
                }
            }
        }

        //check and do the default
        if ( ! ('processData' in setting) ) setting.processData = true;
        if ( ! ('contentType' in setting) ) setting.contentType = 'application/x-www-form-urlencoded';
        if ( ! ('data' in setting)        ) setting.data = null;

        var _param    = setting.data;
        xmlhttp.open('POST', url, true);

        //check and do the setting
        setting.contentType && xmlhttp.setRequestHeader('Content-Type', setting.contentType);
        if ( setting.processData ) _param    = _argumentsEncode(setting.data);

        xmlhttp.send(_param);
    }

    a.load = function(ele, url, args)
    {
        var xmlhttp = createXMLHttp();
        statechange(xmlhttp, function(){
            ele.innerHTML = xmlhttp.responseText;
        });

        var _param = _argumentsEncode(args);
        if ( _param != false )
        {
            if ( url.indexOf('?') != -1 ) url = url + '&' + _param;
            else url = url + '?' + _param;
        }

        xmlhttp.open('GET', url, true);
        xmlhttp.send();
    }

    return a;
}();
