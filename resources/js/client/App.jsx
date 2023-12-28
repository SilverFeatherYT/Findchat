import {createInertiaApp} from '@inertiajs/react'
import {createRoot} from 'react-dom/client'
import {ToastContainer} from "react-toastify";

createInertiaApp({
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.jsx', {eager: true})
        return pages[`./Pages/${name}.jsx`]
    },
    setup({el, App, props}) {
        createRoot(el).render(
            <>
                <App {...props} />
                <ToastContainer
                    newestOnTop
                    theme={'colored'}
                    position={'bottom-center'}
                />
            </>
        )
    },
})
