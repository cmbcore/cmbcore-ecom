// Script chạy 1 lần: node scripts/gen-footer-payload.js
// Output: chuỗi _p để dán vào AdminFooter.jsx

const html = '\u00a9 2006 <a href="https://cmbcore.com" target="_blank">CMB core</a>. All rights reserved - Li\u00ean h\u1ec7 fix l\u1ed7i v\u00e0 ph\u00e1t tri\u1ec3n';
const K = 0x3f;

// Step 1: base64-encode the html
const b64 = Buffer.from(html, 'utf8').toString('base64');

// Step 2: XOR each char with (K + index) & 0xFF
const xored = Array.from(b64).map((c, i) =>
    String.fromCharCode(c.charCodeAt(0) ^ ((K + i) & 0xff)),
).join('');

// Step 3: base64-encode the xored result
const payload = Buffer.from(xored, 'binary').toString('base64');

console.log('Payload:');
console.log(JSON.stringify(payload));
console.log('\nVerify round-trip:');
const back1 = Buffer.from(payload, 'base64').toString('binary');
const back2 = Array.from(back1).map((c, i) =>
    String.fromCharCode(c.charCodeAt(0) ^ ((K + i) & 0xff)),
).join('');
const back3 = Buffer.from(back2, 'base64').toString('utf8');
console.log(back3);
