const fs = require('fs/promises')

async function checkDirectoryAndCreate(dirPath) {
    const dirExist = await fs.access(dirPath)

    if (!dirExist) {
        await fs.mkdir(dirPath)
    }
}

module.exports = { checkDirectoryAndCreate }
