const { checkDirectoryAndCreate } = require('../helpers/checkDirectory')
const testController = require('./initProjectsContent/testControllerPHP')
const testInlineKeyboard = require('./initProjectsContent/testInlineKeyboardPHP')
const testReplyInline = require('./initProjectsContent/testReplyKeyboardPHP')
const testMessage = require('./initProjectsContent/testMessagePHP')
const testView = require('./initProjectsContent/testViewPHP')
const testBotPhp = require('./initProjectsContent/TestBotPHP')
const indexContent = require('./initProjectsContent/indexPHP')
const composerContent = require('./initProjectsContent/composerContent')
const { makeDirectory } = require('../helpers/makeDirectory')
const { makeFile } = require('../helpers/makeFile')
const { botCreator } = require('./resourceCreators/botCreator')
const { viewCreator } = require('./resourceCreators/viewCreator')
const { controllerCreator } = require('./resourceCreators/controllerCreator')
const { replyCreator } = require('./resourceCreators/replyKeyboardCreator')
const { messageCreator } = require('./resourceCreators/messageCreator')
const { inlineCreator } = require('./resourceCreators/inlineKeyboardCreator')

async function addNewResource(resourceName, params) {
    const resourceNames = [
        'bot',
        'controller',
        'inline_keyboard',
        'reply_keyboard',
        'message',
        'view',
    ]

    if (!resourceNames.includes(resourceName)) {
        console.log(
            'Resource name may be: bot | controller | inline_keyboard | reply_keyboard | message | view'
        )
        process.exit(1)
    }

    if (resourceName !== 'bot' && !params.bot) {
        console.log('Need -b, --bot argument')
        process.exit(1)
    }

    if (resourceName === 'bot') {
        await botCreator(params.resourceName)
    } else if (resourceName === 'controller') {
        await controllerCreator(params.bot, params.resourceName)
    } else if (resourceName === 'inline_keyboard') {
        await inlineCreator(params.bot, params.resourceName)
    } else if (resourceName === 'reply_keyboard') {
        await replyCreator(params.bot, params.resourceName)
    } else if (resourceName === 'message') {
        await messageCreator(params.bot, params.resourceName)
    } else if (resourceName === 'view') {
        await viewCreator(params.bot, params.resourceName)
    }
}

module.exports = { addNewResource }
