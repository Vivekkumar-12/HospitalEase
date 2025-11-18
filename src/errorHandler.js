const vercelErrors = require('./vercelErrors');

function getError(code) {
  return vercelErrors[code] || null;
}

function buildResponse(code) {
  const info = getError(code);
  if (!info) {
    return {
      status: 500,
      body: { error: code, category: 'Unknown', message: 'Unknown Vercel error code' }
    };
  }

  return {
    status: info.status,
    body: { error: code, category: info.category }
  };
}

module.exports = { getError, buildResponse };
