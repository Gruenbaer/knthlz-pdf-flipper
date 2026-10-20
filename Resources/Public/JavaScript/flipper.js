import {mapPages, parsePages, selectPages} from './pages.js';
import {pageFromFragment, cornerZone, visiblePages, selectedLeavesForSheet, plainSearchText, bookmarkPage, readerDimensions} from './navigation.js?v=0.4.0-beta.1';

const asset = path => new URL('../Vendor/' + path, import.meta.url).href;
const reduced = matchMedia('(prefers-reduced-motion: reduce)');
let pdfEngine;
async function loadFlipEngine() { await import(asset('page-flip/page-flip.js')); }
async function loadPdfEngine() {
  return pdfEngine ||= import(asset('pdfjs/pdf.js')).then(pdfjs => { pdfjs.GlobalWorkerOptions.workerSrc = asset('pdfjs/pdf.worker.js'); return pdfjs; });
}
function node(tag, className, text) {
  const el = document.createElement(tag);
  if (className) el.className = className;
  if (text !== undefined) el.textContent = text;
  return el;
}
function button(label, text, action) {
  const el = node('button', 'pf-button', text);
  el.type = 'button'; el.title = label; el.setAttribute('aria-label', label);
  el.addEventListener('click', action);
  return el;
}

class PdfFlipper {
  constructor(root) { this.root = root; this.zoom = 1; this.index = 0; this.cache = new Map(); this.renderQueue = Promise.resolve(); this.loaded = false; this.trail = []; this.searchRun = 0; }
  async open() {
    const root = this.root;
    if (root.dataset.initialized) return;
    root.dataset.initialized = 'true';
    try {
      this.options = JSON.parse(root.dataset.pdfFlipper);
      root.dataset.frame = this.options.frame || 'framed';
      if (this.options.sizeAxis === 'width') root.style.width = `${Math.max(200, Math.min(3000, this.options.sizeValue || 800))}px`;
      if (this.options.error) throw new Error(this.options.error);
      // FAL must expose a same-origin public PDF. No implicit remote fetch/proxy.
      this.url = new URL(this.options.pdf, location.href);
      if (this.url.origin !== location.origin || !/^https?:$/.test(this.url.protocol)) throw new Error('Die PDF muss auf derselben Website liegen.');
      root.setAttribute('role', 'region'); root.setAttribute('aria-label', this.options.title || 'PDF-Blätterbuch');
      root.tabIndex = 0;
      if (/^#[a-f0-9]{6}$/i.test(this.options.accent)) root.style.setProperty('--pf-accent', this.options.accent);
      this.message = node('p', 'pf-status', 'PDF wird geladen …'); this.message.setAttribute('role', 'status');
      root.append(this.message);
      for (const [key, variable] of [['background','--pf-background'],['buttonBackground','--pf-button-bg'],['buttonColor','--pf-button-color']]) if (/^#[a-f0-9]{6}$/i.test(this.options[key] || '')) root.style.setProperty(variable, this.options[key]);
      const rgb=(this.options.accent||'#c3323a').slice(1).match(/../g).map(x=>parseInt(x,16)/255).map(c=>c<=.04045?c/12.92:((c+.055)/1.055)**2.4);
      root.style.setProperty('--pf-accent-text',rgb[0]*.2126+rgb[1]*.7152+rgb[2]*.0722>.179?'#000000':'#ffffff');
      this.options.effect ||= 'curl';
      this.options.cornerSensitivity ??= 35; this.options.cornerDelay ??= 120; this.options.duration ??= 650;
      if (this.options.effect === 'curl') await loadFlipEngine();
      if (this.options.preview?.pages?.length) { this.all = this.options.preview.pages; root.dataset.rendering='images'; }
      else { await this.openPdf(); this.all = mapPages(this.pdfSizes, this.options.layout); root.dataset.rendering='pdf'; }
      this.options.readingMode ||= 'auto';
      this.relevant = parsePages(this.options.pages, this.all.length);
      this.pages = selectPages(this.all, this.relevant, this.options.selection);
      const requested = pageFromFragment(location.hash, this.options.contentId) || Number(this.options.start) || this.relevant[0] || 1;
      this.index = Math.max(0, this.pages.findIndex(p => p.number === requested));
      this.storageKey = 'knthlz-pdf-flipper:v1:' + (this.options.preview?.sha256 || this.url.pathname);
      this.createControls();
      this.createBook();
      this.minus.disabled = true;
      root.dataset.pageCount = String(this.pages.length);
      root.dataset.totalPages = String(this.all.length);
      root.dataset.pdfPages = String(this.pdf?.numPages || this.options.preview?.pdfPages || 0);
      root.dataset.ready = 'true';
      this.loaded = true;
      root.querySelector('.pdf-flipper-fallback')?.remove();
      this.message.textContent = this.options.previewError || this.options.notice || '';
      this.update();
      this.readBookmark();
      await this.renderVisible();
      root.dispatchEvent(new CustomEvent('pdf-flipper-ready', {bubbles: true}));
    } catch (error) { this.error(error); }
  }
  async openPdf() {
    if (this.pdf) return;
    const pdfjs=await loadPdfEngine();
    const task=pdfjs.getDocument({url:this.url.href,cMapUrl:asset('pdfjs/cmaps/'),cMapPacked:true,standardFontDataUrl:asset('pdfjs/standard_fonts/'),wasmUrl:asset('pdfjs/wasm/'),isEvalSupported:false,enableXfa:false});
    task.onPassword=()=>{this.error(new Error('Diese PDF benötigt ein Passwort.'));task.destroy();};
    this.pdf=await task.promise;if(this.pdf.numPages>500)throw new Error('Maximal 500 PDF-Seiten erlaubt.');
    this.pdfSizes=[];for(let i=1;i<=this.pdf.numPages;i++){const p=await this.pdf.getPage(i),v=p.getViewport({scale:1});this.pdfSizes.push({width:v.width,height:v.height});}
  }
  async setReadingMode(mode, effect = this.options.effect) {
    const change = this.viewChange = (this.viewChange || 0) + 1;
    if (effect === 'curl') await loadFlipEngine();
    if (change !== this.viewChange) return;
    const index = this.index;
    this.rebuilding = true; this.cleanupCorner?.();
    this.renderGeneration = (this.renderGeneration || 0) + 1;
    this.book?.destroy(); this.book = null;
    this.bookHost.remove(); this.bookHost = node('div', 'pf-book'); this.zoomArea.append(this.bookHost);
    this.options.readingMode = mode; this.options.effect = effect;
    this.cache.clear(); this.index = index; this.stage.scrollTop = 0;
    this.createBook(); this.update();
    this.modeButton?.setAttribute('aria-pressed', String(mode === 'read'));
    if (this.viewSelect) this.viewSelect.value = mode;
    if (this.effectSelect) this.effectSelect.value = effect;
    if (mode === 'scroll') this.stage.scrollTop = this.leaves[this.index].offsetTop * this.zoom;
    this.rebuilding = false;
    await this.renderVisible();
  }
  createControls() {
    const toolbar = node('div', 'pf-toolbar'); toolbar.setAttribute('role', 'group'); toolbar.setAttribute('aria-label', 'Blätterbuch bedienen');
    this.prev = button('Vorherige Seite', '‹', () => this.go(this.index - (this.book ? 1 : this.visibleIndices().length), -1));
    this.next = button('Nächste Seite', '›', () => this.go(this.index + (this.book ? 1 : this.visibleIndices().length), 1));
    this.pageInput = node('input', 'pf-page-input'); this.pageInput.type = 'number'; this.pageInput.min = '1'; this.pageInput.max = String(this.all.length); this.pageInput.setAttribute('aria-label', 'Heftseite');
    this.pageInput.addEventListener('change', () => this.goNumber(Number(this.pageInput.value)));
    this.pageInput.addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); this.goNumber(Number(this.pageInput.value)); } });
    this.pageLabel = node('span', 'pf-page-label'); this.pageLabel.setAttribute('aria-live', 'polite'); this.pageLabel.setAttribute('aria-atomic', 'true');
    this.minus = button('Verkleinern', '−', () => this.setZoom(this.zoom - .5));
    this.plus = button('Vergrößern', '+', () => this.setZoom(this.zoom + .5));
    const full = button('Vollbild', '⛶', () => {
      if (document.fullscreenElement === this.root) document.exitFullscreen();
      else this.root.requestFullscreen?.().catch(() => { this.message.textContent = 'Vollbild ist in diesem Browser nicht verfügbar.'; });
    });
    full.hidden = !document.fullscreenEnabled;
    const download = node('a', 'pf-download', 'PDF herunterladen'); download.href = this.url.href; download.download = ''; download.title = 'Vollständige Original-PDF herunterladen';
    this.modeButton=button('Lesemodus', 'Lesen', ()=>this.setReadingMode(this.options.readingMode==='read'?'auto':'read').catch(error=>this.error(error)));
    this.modeButton.setAttribute('aria-pressed',String(this.options.readingMode==='read'));
    const enabled=new Set(this.options.buttons || ['previous','page','next','zoom','fullscreen','download','reading','view','overview','search','contents','share','bookmark','back']);
    const pageControl=node('span','pf-page-control');pageControl.append(this.pageInput,this.pageLabel);
    for (const [key, elements] of [['previous',[this.prev]],['page',[pageControl]],['next',[this.next]],['zoom',[this.minus,this.plus]],['fullscreen',[full]],['download',[download]],['reading',[this.modeButton]]]) if(enabled.has(key)) toolbar.append(...elements);
    this.root.append(toolbar);
    this.createReadingTools(toolbar, enabled);
    this.stage = node('div', 'pf-stage'); this.stage.setAttribute('aria-label', 'Heftansicht');
    this.zoomArea = node('div', 'pf-zoom-area'); this.bookHost = node('div', 'pf-book'); this.zoomArea.append(this.bookHost); this.stage.append(this.zoomArea); this.root.append(this.stage);
    this.stage.addEventListener('scroll', () => this.followScroll(), {passive:true});
    if (this.relevant.length) {
      const nav = node('nav', 'pf-relevant'); nav.setAttribute('aria-label', 'Relevante Heftseiten');
      nav.append(node('span', '', 'Relevante Seiten:'));
      for (const number of this.relevant) nav.append(button(`Heftseite ${number}`, String(number), () => this.goNumber(number)));
      this.relevantNav = nav; this.root.append(nav);
      if (this.options.selection === 'only') this.root.append(node('p', 'pf-note', 'Auszug – die Seitennummern entsprechen dem vollständigen Heft. Der PDF-Download enthält das gesamte Heft.'));
    }
    this.root.addEventListener('keydown', event => {
      if (event.target.matches('input,select,textarea,button,a')) return;
      if (event.key === 'ArrowRight') { event.preventDefault(); this.go(this.index + 1, 1); }
      if (event.key === 'ArrowLeft') { event.preventDefault(); this.go(this.index - 1, -1); }
      if (event.key === 'Home') { event.preventDefault(); this.go(0); }
      if (event.key === 'End') { event.preventDefault(); this.go(this.pages.length - 1); }
      if (event.key === '+') this.setZoom(this.zoom + .5);
      if (event.key === '-') this.setZoom(this.zoom - .5);
    });
    let flatPress = null;
    this.stage.addEventListener('pointerdown', event => { flatPress={x:event.clientX,y:event.clientY}; }, {passive:true});
    this.stage.addEventListener('pointerup', event => {
      if(!flatPress || this.book || this.zoom>1 || this.options.readingMode==='scroll' || this.options.clickMode==='controls')return;
      const dx=event.clientX-flatPress.x,dy=event.clientY-flatPress.y;flatPress=null;
      if(Math.abs(dx)>40&&Math.abs(dx)>Math.abs(dy)*1.3){const direction=dx<0?1:-1;this.go(this.index+direction*this.visibleIndices().length,direction);}
      else if(Math.hypot(dx,dy)<6){const r=this.bookHost.getBoundingClientRect(),x=event.clientX-r.left,y=event.clientY-r.top;const zone=cornerZone(r.width/(this.visibleIndices().length||1),r.height,Math.max(20,this.options.cornerSensitivity));if(this.options.clickMode==='page'||((y<zone||y>r.height-zone)&&(x<zone||x>r.width-zone))){const direction=x<r.width/2?-1:1;this.go(this.index+direction*this.visibleIndices().length,direction);}}
    }, {passive:true});
    this.stage.addEventListener('pointercancel',()=>{flatPress=null;},{passive:true});
    let pan;
    this.stage.addEventListener('pointerdown', event => { if (this.zoom <= 1) return; pan = {id:event.pointerId,x:event.clientX,y:event.clientY}; this.stage.setPointerCapture(event.pointerId); event.preventDefault(); });
    this.stage.addEventListener('pointermove', event => { if (!pan || pan.id !== event.pointerId) return; this.stage.scrollLeft -= event.clientX-pan.x; this.stage.scrollTop -= event.clientY-pan.y; pan.x=event.clientX; pan.y=event.clientY; });
    for (const name of ['pointerup','pointercancel']) this.stage.addEventListener(name, () => { pan=null; });
    if (this.options.supportUrl) {
      const link = node('a', 'pf-support', 'Wenn Ihnen der Reader hilft, freue ich mich über einen Kaffee.');
      link.href = this.options.supportUrl; link.target = '_blank'; link.rel = 'noopener noreferrer';
      this.root.append(link);
    }
    this.resizeObserver = new ResizeObserver(() => this.resize()); this.resizeObserver.observe(this.root);
  }
  createBook() {
    this.cleanupCorner?.();
    this.creatingCurl = this.pages.length > 1 && !['read','scroll'].includes(this.options.readingMode) && this.options.effect === 'curl';
    this.root.dataset.effect = this.options.effect;
    this.root.dataset.cornerSensitivity = String(this.options.cornerSensitivity);
    this.leaves = this.pages.map(p => {
      const leaf = node('div', 'pf-leaf'); leaf.dataset.page = String(p.number); leaf.setAttribute('aria-label', `Heftseite ${p.number}`);
      if (p.relevant) leaf.classList.add('pf-leaf-relevant');
      const canvas = node('canvas', 'pf-canvas'); canvas.width = canvas.height = 0; canvas.setAttribute('role', 'img'); canvas.setAttribute('aria-label', `Heftseite ${p.number} als PDF-Abbildung`);
      const loading = node('span', 'pf-loading', `Seite ${p.number} wird geladen …`);
      leaf.append(canvas, loading); this.bookHost.append(leaf); return leaf;
    });
    const first = this.pages[0]; this.ratio = first.width / first.height;
    this.resize();
    this.root.dataset.readingMode=this.options.readingMode;
    if (this.pages.length > 1 && !['read','scroll'].includes(this.options.readingMode) && this.options.effect === 'curl') {
      this.book = new window.St.PageFlip(this.bookHost, {
        width: 450, height: Math.round(450 / this.ratio), size: 'stretch', minWidth: this.minimumPageWidth(this.bookHost.clientWidth), maxWidth: 900, minHeight: 100, maxHeight: 1800,
        showCover: first.number === 1, usePortrait: this.options.readingMode !== 'double', autoSize: false, mobileScrollSupport: true,
        flippingTime: reduced.matches ? 1 : this.options.duration, maxShadowOpacity: .4, showPageCorners: !reduced.matches && this.options.cornerSensitivity > 0 && this.options.clickMode !== 'controls',
        useMouseEvents: this.options.clickMode !== 'controls', disableFlipByClick: this.options.clickMode !== 'page', clickEventForward: false, startPage: this.index, swipeDistance: 30,
      });
      this.book.on('flip', event => { this.index = Math.min(event.data, this.pages.length - 1); this.update(); this.renderVisible(); });
      this.book.on('changeOrientation', () => { this.update(); this.renderVisible(); });
      this.book.loadFromHTML(this.leaves);
      this.installCornerHover();
    } else { this.bookHost.classList.add('pf-flat'); this.paintFlat(); }
    this.creatingCurl = false;
    this.resize();
  }
  paintFlat() {
    if (!this.leaves || this.book || this.creatingCurl) return;
    const scrolling = this.options.readingMode === 'scroll';
    const visible = this.visibleIndices();
    this.bookHost.classList.toggle('pf-scroll', scrolling);
    this.bookHost.classList.toggle('pf-pair', !scrolling && visible.length === 2);
    this.leaves.forEach((leaf, i) => { leaf.hidden = !scrolling && !visible.includes(i); });
  }
  followScroll() {
    if (this.options.readingMode !== 'scroll' || this.rebuilding || !this.loaded || this.scrollFrame) return;
    this.scrollFrame = requestAnimationFrame(() => {
      this.scrollFrame = 0;
      const top = this.stage.scrollTop / this.zoom + 20;
      let index = 0;
      for (let i = 0; i < this.leaves.length; i++) { if (this.leaves[i].offsetTop <= top) index = i; else break; }
      if (index !== this.index) { this.index = index; this.update(); this.renderVisible(); }
    });
  }
  createReadingTools(toolbar, enabled) {
    if (enabled.has('view')) {
      const viewLabel = node('label','pf-select-label','Ansicht ');
      this.viewSelect = node('select','pf-select'); this.viewSelect.setAttribute('aria-label','Ansicht');
      for (const [value,label] of [['auto','Heft automatisch'],['single','Einzelseite'],['double','Doppelseite'],['read','Ruhig lesen'],['scroll','Scrollen']]) { const o=node('option','',label);o.value=value;this.viewSelect.append(o); }
      this.viewSelect.value=this.options.readingMode;
      this.viewSelect.addEventListener('change',()=>this.setReadingMode(this.viewSelect.value).catch(error=>this.error(error)));
      viewLabel.append(this.viewSelect); toolbar.append(viewLabel);
      const effectLabel=node('label','pf-select-label','Wechsel ');
      this.effectSelect=node('select','pf-select');this.effectSelect.setAttribute('aria-label','Blättereffekt');
      for(const [value,label]of[['curl','Blättern'],['slide','Schieben'],['none','Sofort']]){const o=node('option','',label);o.value=value;this.effectSelect.append(o);}
      this.effectSelect.value=this.options.effect;
      this.effectSelect.addEventListener('change',()=>this.setReadingMode(this.options.readingMode,this.effectSelect.value).catch(error=>this.error(error)));
      effectLabel.append(this.effectSelect);toolbar.append(effectLabel);
    }
    if(enabled.has('back')){this.backButton=button('Zur letzten Fundstelle','↶ Fundstelle',()=>{const index=this.trail.pop();if(index!==undefined)this.go(index);});this.backButton.disabled=true;toolbar.append(this.backButton);}
    const details=node('details','pf-tools'),summary=node('summary','','Mehr');details.append(summary);
    const tools=node('div','pf-tools-menu');details.append(tools);toolbar.append(details);
    const add=(key,label,text,action)=>{if(!enabled.has(key))return null;const b=button(label,text,()=>{details.open=false;action();});tools.append(b);return b;};
    add('overview','Seitenübersicht','Seitenübersicht',()=>this.showOverview());
    add('search','Im PDF suchen','Suchen',()=>this.showSearch());
    add('contents','PDF-Inhaltsverzeichnis','Inhalt',()=>this.showContents());
    add('share','Link zu dieser Heftseite kopieren','Seitenlink',()=>this.sharePage());
    add('bookmark','Diese Leseposition lokal merken','Hier merken',()=>this.saveBookmark());
    this.resumeButton=add('bookmark','Zum lokalen Lesezeichen','Weiterlesen',()=>{if(this.savedPage)this.goNumber(this.savedPage);});
    if(this.resumeButton)this.resumeButton.hidden=true;
    this.forgetButton=add('bookmark','Lokales Lesezeichen löschen','Lesezeichen löschen',()=>{try{localStorage.removeItem(this.storageKey);this.savedPage=null;this.readBookmark();this.message.textContent='Das lokale Lesezeichen wurde gelöscht.';}catch{this.message.textContent='Der Browser erlaubt hier keinen lokalen Speicher.';}});
    if(this.forgetButton)this.forgetButton.hidden=true;
    if(enabled.has('view')){
      const label=node('label','pf-corner-setting','Seitenecken '),range=node('input');range.type='range';range.min='0';range.max='100';range.value=String(this.options.cornerSensitivity);range.setAttribute('aria-label','Seitenecken-Empfindlichkeit');const output=node('output','',range.value);
      range.addEventListener('input',()=>{this.options.cornerSensitivity=Number(range.value);this.root.dataset.cornerSensitivity=range.value;output.value=range.value;if(this.book)this.book.getSettings().showPageCorners=Number(range.value)>0&&!reduced.matches&&this.options.clickMode!=='controls';});label.append(range,output);tools.append(label);
    }
    if(!tools.children.length)details.hidden=true;
  }
  openPanel(title) {
    this.panel?.remove();this.searchRun++;
    const panel=node('section','pf-panel');panel.setAttribute('aria-label',title);
    const heading=node('h3','',title),close=button('Werkzeug schließen','Schließen',()=>{panel.remove();this.searchRun++;this.root.focus({preventScroll:true});});
    panel.append(heading,close);this.root.insertBefore(panel,this.stage);this.panel=panel;return panel;
  }
  showOverview() {
    const panel=this.openPanel('Seitenübersicht'),grid=node('div','pf-overview');
    for(const page of this.pages){
      const b=button(`Heftseite ${page.number}`,String(page.number),()=>{this.goNumber(page.number);panel.remove();this.root.focus({preventScroll:true});});
      if(page.preview){const image=node('img');image.src=page.preview;image.loading='lazy';image.alt='';image.width=120;image.height=Math.round(120*page.height/page.width);b.prepend(image);}
      grid.append(b);
    }
    panel.append(grid);
  }
  showSearch() {
    const panel=this.openPanel('Im PDF suchen'),form=node('form','pf-search-form'),label=node('label','','Suchbegriff '),input=node('input','pf-search-input');input.type='search';input.maxLength=120;input.required=true;input.minLength=2;input.setAttribute('aria-label','Suchbegriff');label.append(input);const submit=node('button','pf-button','Suchen');submit.type='submit';const status=node('p','pf-search-status');status.setAttribute('role','status');const results=node('div','pf-search-results');form.append(label,submit);panel.append(form,status,results);
    form.addEventListener('submit',async event=>{
      event.preventDefault();const query=input.value.trim().normalize('NFKC').toLocaleLowerCase('de');if(query.length<2)return;
      const run=++this.searchRun;status.textContent='Die PDF wird lokal durchsucht …';results.replaceChildren();
      try{
        await this.openPdf();let count=0;
        for(let physical=1;physical<=this.pdf.numPages;physical++){
          if(run!==this.searchRun||this.panel!==panel||!panel.isConnected)return;
          const leaves=selectedLeavesForSheet(this.pages,physical);if(!leaves.length)continue;
          const text=plainSearchText((await(await this.pdf.getPage(physical)).getTextContent()).items),at=text.indexOf(query);
          if(at<0)continue;
          const item=node('div','pf-search-result');item.append(button(`Treffer auf Heftseite ${leaves.join(' und ')}`,`Heftseite ${leaves.join(' / ')}`,()=>this.goNumber(leaves[0])),node('p','',text.slice(Math.max(0,at-60),at+160).replace(/\s+/g,' ')));results.append(item);count++;
          if(count===30)break;
        }
        if(run===this.searchRun)status.textContent=count?`${count} PDF-Seiten mit Treffern${count===30?' (erste 30)':''}. Bei Doppelseiten werden beide Heftseiten genannt.`:'Kein Treffer. Gescannte Seiten ohne Text lassen sich nicht durchsuchen.';
      }catch{if(run===this.searchRun)status.textContent='Die Textsuche ist für diese PDF nicht verfügbar. Sie können das Original herunterladen.';}
    });input.focus({preventScroll:true});
  }
  async showContents() {
    const panel=this.openPanel('PDF-Inhaltsverzeichnis'),status=node('p','','Inhalt wird geladen …');panel.append(status);
    try{
      await this.openPdf();const outline=await this.pdf.getOutline();if(!panel.isConnected)return;
      if(!outline?.length){status.textContent='Diese PDF enthält kein eingebettetes Inhaltsverzeichnis. Die Seitenübersicht hilft beim Navigieren.';panel.append(button('Seitenübersicht','Seitenübersicht',()=>this.showOverview()));return;}
      let count=0;const list=node('ul','pf-outline');
      const append=async(items,depth=0)=>{for(const item of items){if(++count>200||depth>8)return;let dest=typeof item.dest==='string'?await this.pdf.getDestination(item.dest):item.dest;if(dest?.[0]!==undefined){const physical=(typeof dest[0]==='number'?dest[0]:await this.pdf.getPageIndex(dest[0]))+1;const leaves=selectedLeavesForSheet(this.pages,physical);if(leaves.length){const li=node('li');li.style.marginInlineStart=`${Math.min(depth,4)}em`;li.append(button(String(item.title).slice(0,200),String(item.title).slice(0,200),()=>this.goNumber(leaves[0])));list.append(li);}}if(item.items?.length)await append(item.items,depth+1);}};
      await append(outline);if(!panel.isConnected)return;status.textContent=list.children.length?'':'Die PDF-Lesezeichen verweisen nicht auf die angezeigte Seitenauswahl.';panel.append(list);
    }catch{if(panel.isConnected)status.textContent='Das PDF-Inhaltsverzeichnis ist nicht verfügbar.';}
  }
  async sharePage() {
    const url=new URL(location.href);url.hash=`pf-${this.options.contentId}-p${this.pages[this.index].number}`;
    try{await navigator.clipboard.writeText(url.href);this.message.textContent='Der Link zu dieser Heftseite wurde kopiert.';}
    catch{const panel=this.openPanel('Seitenlink'),input=node('input','pf-link-input');input.readOnly=true;input.setAttribute('aria-label','Link zu dieser Heftseite');input.value=url.href;panel.append(input);input.focus({preventScroll:true});input.select();}
  }
  readBookmark() {
    try{this.savedPage=bookmarkPage(localStorage.getItem(this.storageKey),this.pages.map(p=>p.number));}catch{this.savedPage=null;}
    if(this.resumeButton){this.resumeButton.hidden=!this.savedPage;this.resumeButton.textContent=`Weiter bei Seite ${this.savedPage}`;}
    if(this.forgetButton)this.forgetButton.hidden=!this.savedPage;
  }
  saveBookmark() {
    try{const page=this.pages[this.index].number;localStorage.setItem(this.storageKey,JSON.stringify({page,savedAt:Date.now()}));this.readBookmark();this.message.textContent=`Heftseite ${page} ist auf diesem Gerät gemerkt. Kein Konto, keine Übertragung.`;}
    catch{this.message.textContent='Der Browser erlaubt hier kein lokales Lesezeichen.';}
  }
  installCornerHover() {
    const book = this.book, root = this.root, stage = this.stage;
    const events = new AbortController();
    let dwellTimer = 0, pending = null, entered = '', ready = false;

  const flipController = book.getFlipController();
  if (typeof flipController.showCorner === "function" &&
      typeof flipController.animateFlippingTo === "function") {
    const showCorner = flipController.showCorner.bind(flipController);
    const animateFlippingTo = flipController.animateFlippingTo.bind(flipController);
    let hoverTarget = null;
    let hoverPosition = null;
    let hoverSide = "";
    let hoverFrame = 0;
    let hoverLastFrame = 0;
    this.cleanupCorner = () => { events.abort(); clearTimeout(dwellTimer); cancelAnimationFrame(hoverFrame); this.cleanupCorner = null; };
    flipController.animateFlippingTo = (start, dest, isTurned, needReset = true) => {
      if (!isTurned && needReset === false && book.getState() === "fold_corner") {
        const height = book.getBoundsRect().height;
        const inset = Math.min(3, book.getBoundsRect().pageWidth / 50);
        dest = { x: book.getBoundsRect().pageWidth - inset,
          y: dest.y < height / 2 ? inset : height - inset };
      }
      return animateFlippingTo(start, dest, isTurned, needReset);
    };
    function clearCornerHover(pos) {
      cancelAnimationFrame(hoverFrame);
      hoverFrame = 0;
      hoverLastFrame = 0;
      hoverTarget = hoverPosition = null;
      hoverSide = "";
      if (pos) showCorner(pos);
    }
    function drawCornerHover(now) {
      hoverFrame = 0;
      if (!hoverTarget || (book.getState() !== "read" && book.getState() !== "fold_corner")) return;
      const elapsed = hoverLastFrame ? Math.min(50, now - hoverLastFrame) : 16;
      hoverLastFrame = now;
      const easing = 1 - Math.exp(-elapsed / 65);
      hoverPosition.x += (hoverTarget.x - hoverPosition.x) * easing;
      hoverPosition.y += (hoverTarget.y - hoverPosition.y) * easing;
      showCorner(hoverPosition);
      if (Math.hypot(hoverTarget.x - hoverPosition.x, hoverTarget.y - hoverPosition.y) > 0.35) {
        hoverFrame = requestAnimationFrame(drawCornerHover);
      }
    }
    flipController.showCorner = pos => {
      const rect = book.getBoundsRect();
      const x = pos.x - rect.left;
      const y = pos.y - rect.top;
      const distance = cornerZone(rect.pageWidth, rect.height, this.options.cornerSensitivity);
      const left = x < rect.pageWidth;
      const top = y < rect.height / 2;
      const dx = left ? x : rect.width - x;
      const dy = top ? y : rect.height - y;
      if (!distance || this.options.clickMode === 'controls' || reduced.matches || matchMedia('(pointer: coarse)').matches || this.zoom > 1 ||
          dx <= 0 || dy <= 0 || dx >= distance || dy >= distance) {
        clearTimeout(dwellTimer); dwellTimer = 0; entered = ''; ready = false;
        clearCornerHover({ x: rect.left + rect.pageWidth, y: rect.top + rect.height / 2 });
        return;
      }
      const side = `${left ? "left" : "right"}-${top ? "top" : "bottom"}`;
      pending = pos;
      if (entered !== side) { clearTimeout(dwellTimer); dwellTimer = 0; entered = side; ready = this.options.cornerDelay === 0; }
      if (!ready) {
        if (!dwellTimer) dwellTimer = setTimeout(() => { dwellTimer = 0; ready = true; flipController.showCorner(pending); }, this.options.cornerDelay);
        return;
      }
      if (hoverSide && hoverSide !== side) clearCornerHover({
        x: rect.left + rect.pageWidth, y: rect.top + rect.height / 2,
      });
      // Start with a subtle peek. Closer to the physical corner, the eased
      // fold approaches the pointer until both meet at the page edge.
      // Blend from a 3px peek at the focus boundary to the actual pointer
      // in the inner quarter. Smoothstep keeps both boundaries continuous;
      // a fixed pixel cap would prevent the corner from meeting the pointer.
      const proximity = Math.min(1, (1 - Math.max(dx, dy) / distance) / 0.75);
      const follow = proximity * proximity * (3 - 2 * proximity);
      const insetX = 3 + (dx - 3) * follow;
      const insetY = 3 + (dy - 3) * follow;
      hoverTarget = { x: rect.left + (left ? insetX : rect.width - insetX),
        y: rect.top + (top ? insetY : rect.height - insetY) };
      if (!hoverPosition) {
        hoverPosition = { x: rect.left + (left ? 3 : rect.width - 3),
          y: rect.top + (top ? 3 : rect.height - 3) };
        showCorner(hoverPosition);
        if (book.getState() === "fold_corner") book.getRender().finishAnimation();
      }
      hoverSide = side;
      if (!hoverFrame) hoverFrame = requestAnimationFrame(drawCornerHover);
    };
    stage.addEventListener("pointerdown", () => { clearTimeout(dwellTimer); dwellTimer = 0; clearCornerHover(); }, { capture: true, signal: events.signal });
    let press = null;
    stage.addEventListener('pointerdown', event => { press = {x:event.clientX,y:event.clientY}; }, {signal:events.signal});
    stage.addEventListener('click', event => {
      if (this.options.clickMode !== 'corners' || this.zoom > 1 || !press || Math.hypot(event.clientX-press.x,event.clientY-press.y)>6) return;
      const r = this.bookHost.getBoundingClientRect();
      const zone = cornerZone(book.getBoundsRect().pageWidth, r.height, Math.max(20,this.options.cornerSensitivity));
      const x=event.clientX-r.left,y=event.clientY-r.top;
      if ((y<zone || y>r.height-zone) && (x<zone || x>r.width-zone)) this.go(this.index+(x<r.width/2?-1:1),x<r.width/2?-1:1);
    }, {signal:events.signal});
    book.on("changeState", event => {
      if (event.data === "flipping" || event.data === "user_fold") clearCornerHover();
    });
    root.addEventListener("mouseleave", () => {
      clearTimeout(dwellTimer); dwellTimer=0; entered=""; ready=false;
      const rect = book.getBoundsRect();
      clearCornerHover({ x: rect.left + rect.pageWidth, y: rect.top + rect.height / 2 });
    }, {signal: events.signal});
  }
  }
  minimumPageWidth(width) { return this.options.readingMode==='single' ? Math.floor(width*.95) : this.options.readingMode==='double' ? Math.max(80,Math.floor(width*.45)) : Math.min(280,Math.floor(width*.95)); }
  resize() {
    if (!this.stage || !this.ratio) return;
    const {height, bookWidth} = readerDimensions(this.stage.clientWidth, this.ratio, this.options.readingMode, this.options.sizeAxis, this.options.sizeValue);
    this.stage.style.height = `${height + 8}px`;
    this.zoomArea.style.width = `${bookWidth * this.zoom}px`; this.zoomArea.style.height = `${(this.options.readingMode === 'scroll' ? (height + 12) * this.pages.length : height) * this.zoom}px`;
    this.bookHost.style.width = `${bookWidth}px`; this.bookHost.style.height = this.options.readingMode === 'scroll' ? 'auto' : `${height}px`;
    this.leaves?.forEach(leaf => { if (!this.book) leaf.style.height = `${height}px`; }); this.bookHost.style.transform = `scale(${this.zoom})`;
    if (this.book) { this.book.getSettings().minWidth = this.minimumPageWidth(bookWidth); this.bookHost.style.minWidth = `${this.minimumPageWidth(bookWidth) * (this.options.readingMode === 'double' ? 2 : 1)}px`; this.book.update(); }
    if (!this.book) this.paintFlat();
    if (this.loaded) this.renderVisible();
  }
  visibleIndices() {
    if (!this.book) {
      const double = !['single','read','scroll'].includes(this.options.readingMode) && (this.options.readingMode === 'double' || this.stage.clientWidth >= 640);
      return visiblePages(this.index,this.pages.length,double,this.pages[0].number===1);
    }
    const double = this.book?.getOrientation() === 'landscape' && !(this.pages[0].number === 1 && this.index === 0);
    // Library's current index is the first visible page in a spread.
    return [...new Set([this.index, ...(double ? [this.index + 1] : [])])].filter(i => i < this.pages.length);
  }
  update() {
    if (!this.pageInput) return;
    const indices = this.visibleIndices();
    const numbers = indices.map(i => this.pages[i].number);
    this.pageInput.value = String(numbers[0]); this.prev.disabled = this.index === 0; this.next.disabled = this.index >= this.pages.length - 1 || indices.includes(this.pages.length - 1);
    this.pageLabel.textContent = ` / ${this.all.length}${numbers.length > 1 ? ' · ' + numbers.join('–') : ''}`;
    this.root.dataset.currentPage = String(numbers[0]);
    if(this.backButton)this.backButton.disabled=!this.trail.length;
    if (this.relevantNav) for (const b of this.relevantNav.querySelectorAll('button')) b.setAttribute('aria-pressed', String(numbers.includes(Number(b.textContent))));
  }
  goNumber(number) {
    const index = this.pages.findIndex(p => p.number === number);
    if (index < 0) { this.message.textContent = 'Diese Seite gehört nicht zur Auswahl.'; this.update(); return; }
    if (index !== this.index) { this.trail.push(this.index); this.trail=this.trail.slice(-20); }
    this.go(index);
  }
  go(index, direction = 0) {
    if (!this.book) {
      this.index = Math.max(0,Math.min(this.pages.length-1,index)); this.paintFlat();
      if (this.options.readingMode === 'scroll') this.stage.scrollTo({top:this.leaves[this.index].offsetTop*this.zoom,behavior:'instant'});
      else if (this.options.effect==='slide' && !reduced.matches && direction) for (const i of this.visibleIndices()) this.leaves[i].animate([{transform:`translateX(${direction*6}%)`,opacity:.7},{transform:'translateX(0)',opacity:1}],{duration:Math.min(500,this.options.duration),easing:'ease-out'});
      this.update(); this.renderVisible(); return;
    }
    this.message.textContent = '';
    index = Math.max(0, Math.min(this.pages.length - 1, index));
    if (reduced.matches || this.zoom > 1 || !direction) this.book.turnToPage(index);
    else if (direction > 0) this.book.flipNext('bottom'); else this.book.flipPrev('bottom');
    this.index = Math.min(this.book.getCurrentPageIndex(), this.pages.length - 1);
    this.update(); this.renderVisible();
  }
  setZoom(value) {
    this.zoom = Math.max(1, Math.min(3, value)); this.minus.disabled = this.zoom === 1; this.plus.disabled = this.zoom === 3;
    this.root.dataset.zoom = String(this.zoom); this.bookHost.style.pointerEvents = this.zoom > 1 ? 'none' : ''; this.resize();
  }
  async renderVisible() {
    if ((!this.pdf && !this.options.preview) || !this.leaves) return;
    const generation = this.renderGeneration = (this.renderGeneration || 0) + 1;
    const focus = this.visibleIndices();
    const targets = [...new Set([...focus, this.index - 1, this.index + 1, this.index + 2, this.index + 3])].filter(i => i >= 0 && i < this.pages.length);
    // Serialize PDF rendering and keep at most eight raster pages per reader.
    this.renderQueue = this.renderQueue.catch(() => {}).then(async () => {
      for (const index of targets) { if (generation !== this.renderGeneration) return; await this.renderPage(index); }
      for (const [index] of this.cache) if (this.cache.size > 8 && !targets.includes(index)) {
        const canvas = this.leaves[index].querySelector('canvas'); canvas.width = canvas.height = 0; this.cache.delete(index); delete this.leaves[index].dataset.rendered;
        this.leaves[index].querySelector('.pf-loading').hidden = false;
      }
    }).catch(error => this.error(error));
    return this.renderQueue;
  }
  async renderPage(index) {
    const part = this.pages[index], leaf = this.leaves[index];
    if(this.options.preview && !this.previewFailed){
      const high=this.zoom>1 || (devicePixelRatio||1)>1.4,level=high?2:1;
      if((this.cache.get(index)||0)>=level)return;
      try{
        const image=new Image();image.src=part[high?'hd':'preview'];await image.decode();
        if(leaf!==this.leaves[index])return;
        const canvas=leaf.querySelector('canvas');canvas.width=image.naturalWidth;canvas.height=image.naturalHeight;canvas.getContext('2d',{alpha:false}).drawImage(image,0,0);
        this.cache.delete(index);this.cache.set(index,level);leaf.querySelector('.pf-loading').hidden=true;leaf.dataset.rendered='true';leaf.dataset.imageFormat=this.options.format;return;
      }catch(error){this.previewFailed=true;this.cache.clear();await this.openPdf();this.root.dataset.rendering='pdf-fallback';this.message.textContent='Die Vorschau wird aus der PDF geladen.';}
    }
    const wanted = Math.min(2800, Math.max(700, Math.ceil(this.bookHost.clientHeight * Math.min(2, devicePixelRatio || 1) * this.zoom * 1.5)));
    if ((this.cache.get(index) || 0) >= wanted) return;
    const page = await this.pdf.getPage(part.pdfPage), base = page.getViewport({scale: 1});
    const scale = Math.min(wanted / base.height, 4096 / base.width);
    const canvas = leaf.querySelector('canvas'); canvas.width = Math.ceil(part.width * scale); canvas.height = Math.ceil(part.height * scale);
    const viewport = page.getViewport({scale, offsetX: part.half === 'right' ? -base.width * scale / 2 : 0});
    await page.render({canvasContext: canvas.getContext('2d', {alpha: false}), viewport}).promise;
    if(leaf!==this.leaves[index])return;
    this.cache.delete(index); this.cache.set(index, Math.round(base.height * scale)); leaf.querySelector('.pf-loading').hidden = true;
    leaf.dataset.rendered = 'true';
  }
  error(error) {
    console.error('[PDF Flipper]', error);
    this.root.dataset.error = 'true';
    if (!this.message) { this.message = node('p', 'pf-status'); this.root.append(this.message); }
    this.message.setAttribute('role', 'alert');
    this.message.textContent = 'Das Blätterbuch konnte nicht geladen werden. ' + (error.message || 'Bitte die PDF direkt öffnen.');
    if (this.url && !this.root.querySelector('a')) { const a = node('a', 'pf-download', 'PDF direkt öffnen'); a.href = this.url.href; this.root.append(a); }
  }
}
const observer = new IntersectionObserver(entries => { for (const entry of entries) if (entry.isIntersecting) { observer.unobserve(entry.target); new PdfFlipper(entry.target).open(); } }, {rootMargin: '400px'});
for (const root of document.querySelectorAll('[data-pdf-flipper]')) observer.observe(root);
