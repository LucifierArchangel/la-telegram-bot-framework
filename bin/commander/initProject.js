const fs = require('fs/promises')
const composerContent = require('./initProjectsContent/composerContent')
const indexContent = require('./initProjectsContent/indexPHP')
const testBotPhp = require('./initProjectsContent/TestBotPHP')
const testController = require('./initProjectsContent/testControllerPHP')
const testInlineKeyboard = require('./initProjectsContent/testInlineKeyboardPHP')
const testMessage = require('./initProjectsContent/testMessagePHP')
const testReplyInline = require('./initProjectsContent/testReplyKeyboardPHP')
const testView = require('./initProjectsContent/testViewPHP')

async function makeFile(currentPath, file) {
    await fs.writeFile(
        `${currentPath}/${file.name}`,
        file.content ? file.content : '',
        { encoding: 'utf-8' }
    )
}

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

async function initProject(name, initDb) {
    const projectStructure = [
        {
            type: 'dir',
            name: 'src',
            sub: [
                {
                    type: 'dir',
                    name: 'Bots',
                    sub: [
                        {
                            type: 'dir',
                            name: 'TestBot',
                            sub: [
                                {
                                    type: 'dir',
                                    name: 'Controllers',
                                    sub: [
                                        {
                                            type: 'file',
                                            name: 'TestController.php',
                                            content: testController,
                                        },
                                    ],
                                },
                                {
                                    type: 'dir',
                                    name: 'Views',
                                    sub: [
                                        {
                                            type: 'dir',
                                            name: 'Keyboards',
                                            sub: [
                                                {
                                                    type: 'dir',
                                                    name: 'Inline',
                                                    sub: [
                                                        {
                                                            type: 'file',
                                                            name: 'TestInlineKeyboard.php',
                                                            content:
                                                                testInlineKeyboard,
                                                        },
                                                    ],
                                                },
                                                {
                                                    type: 'dir',
                                                    name: 'Reply',
                                                    sub: [
                                                        {
                                                            type: 'file',
                                                            name: 'TestReplyKeyboard.php',
                                                            content:
                                                                testReplyInline,
                                                        },
                                                    ],
                                                },
                                            ],
                                        },
                                        {
                                            type: 'dir',
                                            name: 'Messages',
                                            sub: [
                                                {
                                                    type: 'file',
                                                    name: 'TestMessage.php',
                                                    content: testMessage,
                                                },
                                            ],
                                        },
                                        {
                                            type: 'file',
                                            name: 'TestView.php',
                                            content: testView,
                                        },
                                    ],
                                },
                                {
                                    type: 'file',
                                    name: 'TestBot.php',
                                    content: testBotPhp,
                                },
                            ],
                        },
                    ],
                },
                {
                    type: 'file',
                    name: 'index.php',
                    content: indexContent,
                },
            ],
        },
        {
            type: 'file',
            name: 'composer.json',
            content: composerContent,
        },
    ]

    try {
        await fs.mkdir(`./${name}`)
    } catch (e) {}

    for (const item of projectStructure) {
        if (item.type === 'dir') {
            await makeDirectory(`./${name}`, item)
        } else if (item.type === 'file') {
            await makeFile(`./${name}`, item)
        }
    }
}

module.exports = {
    initProject,
}
