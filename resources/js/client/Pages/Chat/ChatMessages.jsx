import {Fragment, useEffect, useRef} from "react";

export default function ChatMessages({messages, receiverID, searchTerm, currentHighlightedIndex, setHighlightedIndices }) {

    //
    const isReceivedMessage = (message) => {
        return message.sender_id === receiverID;
    };


    // Highlight text
    useEffect(() => {
        if (searchTerm) {
            const newHighlightedIndices = messages
                .map((m, index) => m.message.toLowerCase().includes(searchTerm.toLowerCase()) ? index : -1)
                .filter(index => index !== -1);

            setHighlightedIndices(newHighlightedIndices);

            if (newHighlightedIndices[currentHighlightedIndex] !== undefined) {
                document.getElementById(`message-${newHighlightedIndices[currentHighlightedIndex]}`)
                    ?.scrollIntoView({ behavior: "smooth", block: "nearest" });
            }
        }
    }, [searchTerm, messages, currentHighlightedIndex]);

    const highlightText = (text) => {
        if (!searchTerm) return text;
        const regex = new RegExp(searchTerm, 'gi');
        return text.replace(regex, match => `<mark>${match}</mark>`);
    };

    const bottomRef = useRef(null);

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: "smooth" });
    }, [messages]);

    return (
        <div className="tw-overflow-y-auto tw-p-4">
            {(messages || []).map((message, index) => (
                <div key={index} id={`message-${index}`} className="message-container">
                    <Fragment key={index}>
                        {message?.custom_date ? (
                            <div className={'tw-flex tw-justify-center tw-mb-2'}>
                                <span className={'tw-bg-white tw-rounded-full tw-p-2 tw-text-xs'}>{message?.custom_date }</span>
                            </div>
                        ) : (
                            ''
                        )}

                        <div
                            className={`${
                                isReceivedMessage(message)
                                    ? "receive-chat tw-justify-start"
                                    : "send-chat tw-justify-end"
                            } tw-relative tw-flex`}
                        >
                            <div
                                className={`tw-mb-2 tw-max-w-[80%] tw-rounded-lg ${
                                    isReceivedMessage(message)
                                        ? "bg-primary"
                                        : "tw-bg-white"
                                } tw-px-4 tw-py-2 tw-text-sm ${
                                    isReceivedMessage(message)
                                        ? "tw-text-white"
                                        : "tw-text-slate-600"
                                }`}
                            >
                                <div>
                                    <span className="tw-flex-grow tw-text-base" style={{wordBreak: 'break-word'}} dangerouslySetInnerHTML={{ __html: highlightText(message.message) }} />
                                    <span className="tw-text-xs tw-ml-4 tw-mt-2 tw-float-right">{message?.custom_time}</span>
                                </div>
                            </div>
                        </div>
                    </Fragment>
                </div>
            ))}
            <div ref={bottomRef} />
        </div>

    );
}
