const testController = require('../initProjectsContent/testControllerPHP')
const testInlineKeyboard = require('../initProjectsContent/testInlineKeyboardPHP')
const testReplyInline = require('../initProjectsContent/testReplyKeyboardPHP')
const testMessage = require('../initProjectsContent/testMessagePHP')
const testView = require('../initProjectsContent/testViewPHP')
const testBotPhp = require('../initProjectsContent/TestBotPHP')
const indexContent = require('../initProjectsContent/indexPHP')
const composerContent = require('../initProjectsContent/composerContent')
const { makeDirectory } = require('../../helpers/makeDirectory')
const { makeFile } = require('../../helpers/makeFile')

async function messageCreator(botName, messageName) {
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
                            name: `${botName}Bot`,
                            sub: [
                                {
                                    type: 'dir',
                                    name: 'Views',
                                    sub: [
                                        {
                                            type: 'dir',
                                            name: 'Messages',
                                            sub: [
                                                {
                                                    type: 'file',
                                                    name: `${messageName}Message.php`,
                                                    content: testMessage(
                                                        `${botName}Bot`,
                                                        `${messageName}Message`
                                                    ),
                                                },
                                            ],
                                        },
                                    ],
                                },
                            ],
                        },
                    ],
                },
            ],
        },
    ]

    for (const item of projectStructure) {
        if (item.type === 'dir') {
            await makeDirectory(`./`, item)
        } else if (item.type === 'file') {
            await makeFile(`./`, item)
        }
    }
}

module.exports = { messageCreator }
