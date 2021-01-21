const QPACT_VERSION = 'D2';
function qDescribe(input) {
  return `${
    input != undefined ? `<${input.constructor.name}>` : ''
  }${input}`;
}


// Q
function q(input) {
  if(input == null) return null;
  switch(input.constructor){
    case String:
      var template = document.createElement('template');
      template.innerHTML = input;
      var children = template.content.childNodes;
      return children.length > 1 ? [...children] : children[0];
    case Array:
      return input.map((e) => q(e));
    case Node:
      return input;
    case RegExp:
      return document.q(input);
    default:
      throw TypeError(
        `input expected type (RegExp | string | Node | Array | null), found ${
          qDescribe(input)
        }`
      );
  }
}

Node.prototype.Q = function (input) {
  var first;
  while ((first = this.firstChild) !== null)
    this.removeChild(first);
  this.q(input);
};
Node.prototype.q = function (input) {
  if(input == null) return null;
  switch(input.constructor){
    case RegExp:
      return input.global
        ? [...this.querySelectorAll(input.source)]
        : this.querySelector(input.source);
    default:
      var x = q(input);
      switch(x.constructor){
        case Node:
          this.appendChild(x);
        case Array:
          for(var e of x)
            this.appendChild(e);
      }
      return x;
  }
};


// Pact
var pactComponents = {};
function defineComponent(name, Class) {
  try {
    document.querySelector(name);
  } catch (e) {
    throw SyntaxError(`${name} is not a valid css selector`);
  }
  Class.events = getEvents(Class);
  pactComponents[name] = Class;
}
function getEvents(Class, prefix='on'){
  var ClassEvents = new Set();
  var Current = Class;
  for(; Current.name; Current = Object.getPrototypeOf(Current)) {
    var Prototype = Current.prototype;
    for (var key of Reflect.ownKeys(Prototype)) {
      var descriptor = Object.getOwnPropertyDescriptor(Prototype, key);
      if (
        Reflect.has(descriptor, 'value')
        && (key.constructor === String)
        && key.startsWith(prefix)
      )
        ClassEvents.add(key.slice(prefix.length));
    }
  }
  return ClassEvents;
}

function* pactWalk(e) {
  // yield elements bottom-up
  for(var c of e.children || [])
    for(var d of pactWalk(c))
      yield d;
  yield e;
}
function pactInit(root){
  var tags = Object.keys(pactComponents).reverse();
  for(var e of pactWalk(root)){
    if(!e.c) {
      var tag = tags.find(t =>
        (e.matches && t.constructor === String) ? e.matches(t) : e === t
      );
      var Class = tag ? pactComponents[tag] : Component;
      Class.init(e);
    }
    if(!e.c.loaded) {
      e.c.load && e.c.load();
      e.c.loaded = true;
    }
  }
}
function pactUpdate(mutationRecords){
  for(var mr of mutationRecords){
    for(var e of mr.addedNodes)
      pactInit(e);
    for(var f of mr.removedNodes)
      if(f.c){
        f.c.unload && f.c.unload();
        f.c.loaded = false;
      }
  }
}

document.addEventListener('DOMContentLoaded', () => {
  pactInit(document.documentElement);
  var observer = new MutationObserver(pactUpdate);
  observer.observe(document.documentElement, {
    subtree: true,
    childList: true, /* includes Text changes because js is stupid */
  });
});


// Components
class Component {
  static init(e){
    var self = new this;

    self.e = e;
    e.c = self;

    for (var k of this.events)
      e.addEventListener(k, self[`on${k}`].bind(self));
  }
  alter(){
    this.e.dispatchEvent(
      new CustomEvent('alter', {
        bubbles: true,
        cancelable: true,
      })
    );
  }
  get disabled(){
    return this.e.hasAttribute('disabled');
  }
  set disabled(value){
    if(value)
      this.e.setAttribute('disabled', '');
    else
      this.e.removeAttribute('disabled');
    for(var e of this.e.children)
      e.c.disabled = value;
  }
}
defineComponent('*', Component);

class InputComponent extends Component {
  oninput(ev){
    this.alter();
  }
}
defineComponent('input', InputComponent);
