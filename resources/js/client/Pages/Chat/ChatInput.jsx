import {router, useForm} from "@inertiajs/react";
import React, {useState} from "react";
import {mdiSend} from "@mdi/js";
import Icon from "@mdi/react";
import {Spinner} from "react-bootstrap";
import {collection, addDoc, getFirestore, onSnapshot } from "firebase/firestore"
import {firebaseApp} from "@/Vendor/Firebase";
import Push from 'push.js';

export default function ChatInput({ receiver }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        message: "",
    });

    const onHandleChange = (event) => {
        setData(event.target.name, event.target.value);
    };

    const [isLoading, setIsLoading] = useState(false);

    const submit = async (e) => {
        if (!data.message.trim()) {
            // If message is empty or contains only whitespace, don't submit
            return;
        }
        setIsLoading(true);

        // e.preventDefault();
        router.post(`/chat/send/${receiver[0]?.firebase_uid}`, data, {
            onSuccess: async () => {
                console.log('Message sent successfully');
                const db = getFirestore(firebaseApp);

                // Add a new document with a generated id to the "messages" collection
                await addDoc(collection(db, "messages"), {
                    messages: data,
                });
                setIsLoading(false)
                // Handle successful storage
                console.log('Message stored in Firestore successfully.');
            },
            onError: (err) => {
                console.warn(err);
            },
            onFinish: () => {
                setIsLoading(false);
            },
        });
        reset("message");
    };

    const handleKeyPress = (event) => {
        if (event.key === "Enter" && !event.shiftKey) {
            event.preventDefault();
            submit();
        }
    };


    return (
        <div className="input-field tw-bg-gray-300 tw-px-5 tw-py-3 tw-sticky tw-bottom-0 tw-flex tw-items-center">
            <div className="tw-relative tw-w-full">
                <input
                    type="text"
                    placeholder="Type a message"
                    className="tw-w-full tw-py-2 tw-pl-4 tw-pr-10 tw-border tw-rounded-md"
                    name="message"
                    value={data.message}
                    onChange={onHandleChange}
                    onKeyDown={handleKeyPress}
                    autoFocus
                />
                <div
                    className="tw-absolute tw-inset-y-0 tw-right-0 tw-pr-3 tw-flex tw-items-center text-sm leading-5"
                    onClick={submit}
                >
                    {isLoading ? (
                        <Spinner variant={'primary'} animation={'grow'} size={'sm'}/>
                    ) : (
                        <Icon path={mdiSend} size={1} className="tw-cursor-pointer" />
                    )}
                </div>
            </div>
        </div>
    );
}
