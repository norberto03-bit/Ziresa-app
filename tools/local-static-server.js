const http = require('http');
const fs = require('fs');
const path = require('path');

const root = process.cwd();
const port = Number(process.argv[2] || 8765);
const types = {
  '.html': 'text/html; charset=utf-8',
  '.css': 'text/css; charset=utf-8',
  '.js': 'text/javascript; charset=utf-8',
  '.json': 'application/json; charset=utf-8',
  '.svg': 'image/svg+xml',
  '.xml': 'application/xml; charset=utf-8',
  '.txt': 'text/plain; charset=utf-8',
  '.webmanifest': 'application/manifest+json; charset=utf-8'
};

http.createServer((req, res) => {
  let pathname = new URL(req.url, 'http://localhost').pathname;
  if(pathname === '/') pathname = '/index.html';
  const filePath = path.join(root, decodeURIComponent(pathname));
  if(!filePath.startsWith(root)) {
    res.writeHead(403);
    res.end('forbidden');
    return;
  }
  fs.readFile(filePath, (error, data) => {
    if(error) {
      res.writeHead(404);
      res.end('not found');
      return;
    }
    res.writeHead(200, { 'Content-Type': types[path.extname(filePath)] || 'application/octet-stream' });
    res.end(data);
  });
}).listen(port, '127.0.0.1');
