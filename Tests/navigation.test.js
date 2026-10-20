import test from 'node:test';
import assert from 'node:assert/strict';
import {pageFromFragment,cornerZone,visiblePages,selectedLeavesForSheet,plainSearchText,bookmarkPage} from '../Resources/Public/JavaScript/navigation.js';
test('page links target one content element and reject malformed fragments',()=>{
  assert.equal(pageFromFragment('#pf-4971-p12',4971),12);
  for(const hash of ['#pf-4972-p12','#pf-4971-p0','#pf-4971-p2x','#pf-4971-p-1','#pf-4971-p999999'])assert.equal(pageFromFragment(hash,4971),null);
});
test('corner off is fully off; larger sensitivity grows a bounded hit zone',()=>{
  assert.equal(cornerZone(450,650,0),0);
  assert.ok(cornerZone(450,650,10)<cornerZone(450,650,35));
  assert.ok(cornerZone(450,650,35)<cornerZone(450,650,100));
  assert.ok(cornerZone(80,100,100)<=36);
});
test('flat spreads keep covers and final unpaired pages single',()=>{
  assert.deepEqual(visiblePages(0,32,true,true),[0]);
  assert.deepEqual(visiblePages(1,32,true,true),[1,2]);
  assert.deepEqual(visiblePages(31,32,true,true),[31]);
  assert.deepEqual(visiblePages(0,7,true,false),[0,1]);
  assert.deepEqual(visiblePages(4,7,false,false),[4]);
});
test('sheet search reports only logical leaves in the selected excerpt',()=>{
  const p=[{pdfPage:2,number:2},{pdfPage:2,number:3},{pdfPage:3,number:4}];
  assert.deepEqual(selectedLeavesForSheet(p,2),[2,3]);assert.deepEqual(selectedLeavesForSheet(p.slice(1),2),[3]);assert.deepEqual(selectedLeavesForSheet(p,9),[]);
});
test('PDF text normalization handles compatibility Unicode without HTML parsing',()=>{
  assert.equal(plainSearchText([{str:'EPILEPSIE',hasEOL:true},{str:'ﬁnden'},{type:'image'}]),'epilepsie\nfinden ');
});
test('bookmarks reject corrupt, unavailable and non-integer pages',()=>{
  assert.equal(bookmarkPage('{"page":12}',[3,12]),12);
  for(const raw of [null,'broken','{}','{"page":4}','{"page":"12"}','{"page":12.5}'])assert.equal(bookmarkPage(raw,[3,12]),null);
});

import {readerDimensions} from '../Resources/Public/JavaScript/navigation.js';
test('container sizing, one axis, responsive aspect ratio and small screens',()=>{
  const ratio=595/842;
  const auto=readerDimensions(1000,ratio,'auto'); assert.equal(auto.bookWidth,1000); assert.equal(auto.height,1000/(ratio*2));
  const h=readerDimensions(1000,ratio,'single','height',480); assert.equal(h.height,480); assert.equal(h.bookWidth,480*ratio);
  const w=readerDimensions(1000,ratio,'auto','width',500); assert.equal(w.bookWidth,500); assert.equal(w.single,true);
  for(const mode of ['auto','double','single','read','scroll']) for(const axis of ['auto','width','height']) {
    const r=readerDimensions(248,ratio,mode,axis,2000);assert.ok(r.bookWidth<=248);assert.ok(r.height>0);assert.ok(Math.abs(r.bookWidth/r.height-ratio*(r.single?1:2))<.00001);
  }
});
