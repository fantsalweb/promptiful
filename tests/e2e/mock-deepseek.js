/**
 * DeepSeek simulado para las pruebas: no hace falta API key ni red.
 * - Primera llamada de una conversación -> preguntas (salvo peticiones que contengan «noticias»).
 * - Con respuestas del usuario o con la orden de «no más preguntas» -> prompt final.
 * - Peticiones que contengan «rota» -> respuesta que no es JSON válido.
 */
const http = require('http');

function start(port) {
  const server = http.createServer((req, res) => {
    let raw = '';
    req.on('data', c => { raw += c; });
    req.on('end', () => {
      const send = (status, obj) => { res.writeHead(status, { 'Content-Type': 'application/json' }); res.end(JSON.stringify(obj)); };
      if (req.headers.authorization !== 'Bearer test-key') return send(401, { error: 'unauthorized' });
      const { messages } = JSON.parse(raw);
      const system = messages[0].content;
      const assistantCount = messages.filter(m => m.role === 'assistant').length;
      const first = messages.find(m => m.role === 'user').content.toLowerCase();
      const force = system.includes('INSTRUCCIÓN ACTUAL: ya no hagas más preguntas');
      const news = first.includes('noticias');
      let out;
      if (first.includes('rota')) {
        out = 'esto no es json';
      } else if (!assistantCount && !force && !news) {
        out = JSON.stringify({ type: 'questions', intro: 'Dime más', questions: [{ apartado: 'tema', pregunta: '¿Qué artista?' }, '¿Dónde?'] });
      } else {
        out = JSON.stringify({ type: 'final', prompt: {
          tipo_salida: news ? 'texto' : 'imagen', necesita_fuentes: news ? 'true' : false,
          rol: 'R', contexto: 'C', tema: 'T', formato: 'F', tono: 'To', ejemplo: 'E',
          restricciones: ['a', 'b'], indicadores_exito: 'I', orden_final: 'Genera',
        } });
      }
      send(200, { choices: [{ message: { content: out } }], usage: { prompt_tokens: 100, completion_tokens: 50 } });
    });
  });
  return new Promise(resolve => server.listen(port, '127.0.0.1', () => resolve(server)));
}

module.exports = { start };
