const fs = require('fs/promises')

async function makeFile(currentPath, file) {
    await fs.writeFile(
        `${currentPath}/${file.name}`,
        file.content ? file.content : '',
        { encoding: 'utf-8' }
    )
}

module.exports = { makeFile }
