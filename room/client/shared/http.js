function createRequestBody(x){
  if(x.constructor !== Array)
    x = Object.entries(x);
  return x.map(([k, v]) => `${k}=${v}`).join('&');
}

function fetchTxt(...args){
  return fetch(...args).then((r) => r.text());
}
