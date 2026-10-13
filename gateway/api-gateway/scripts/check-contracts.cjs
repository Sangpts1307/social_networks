const SwaggerParser = require('@apidevtools/swagger-parser');
const path = require('node:path');
(async () => {
  for (const name of ['identity-v1.yaml', 'gateway-identity-v1.yaml', 'admin-identity-v1.yaml', 'learning-v1.yaml', 'gateway-learning-v1.yaml', 'social-v1.yaml', 'gateway-social-v1.yaml']) {
    const file = path.resolve(__dirname, '../../../contracts/openapi', name);
    await SwaggerParser.validate(file);
    console.log(`PASS OpenAPI: ${name}`);
  }
})().catch(error => { console.error(error.message); process.exit(1); });
