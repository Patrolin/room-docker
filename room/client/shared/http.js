function createRequestBody(x){
  if(x.constructor !== Array)
    x = Object.entries(x);
  return x.map(([k, v]) => `${k}=${v}`).join('&');
}

function fetchTxt(...args){
  return fetch(...args).then((r) => r.text());
}

function getCookie(key){
  for(var x of document.cookie.split('; ')){
    var m = x.match('([^=]*)=(.*)');
    if(m){
      if(m[1] === key) return m[2];
    } else{
      if(key === '') return x;
    }
  }
}
function setCookie(value){
  document.cookie = value;
}
function deleteCookie(key){
  document.cookie = `${key}=; expires=Thu, 01 Jan 1970 00:00:01 GMT;`;
}

function redirect(url){
  window.location.href = url;
}
