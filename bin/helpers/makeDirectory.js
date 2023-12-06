const fs = require('fs/promises')
const { makeFile } = require('./makeFile')

async function makeDirectory(currentPath, directory) {
    const dirPath = `${currentPath}/${directory.name}`

    try {
        await fs.mkdir(dirPath)
    } catch (e) {}

    if (directory.sub) {
        for (const item of directory.sub) {
            if (item.type === 'dir') {
                await makeDirectory(dirPath, item)
            } else if (item.type === 'file') {
                await makeFile(dirPath, item)
            }
        }
    }
}

module.exports = { makeDirectory }
