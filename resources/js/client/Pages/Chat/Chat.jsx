import ChatUserInfoHeader from "@/Pages/Chat/ChatUserInfoHeader";
import ChatMessages from "@/Pages/Chat/ChatMessages";
import ChatInput from "@/Pages/Chat/ChatInput";
import React, {useEffect, useRef, useState} from "react";
import {Link, usePage} from "@inertiajs/react";
import {Button, Card, CardBody, Col, Modal, Nav} from "react-bootstrap";
import ChatSidebar from "@/Pages/Chat/ChatSideBar";
import {db, firebaseApp} from "@/Vendor/Firebase";
import {collection, getDocs, getFirestore, onSnapshot } from "firebase/firestore"
import Icon from "@mdi/react";
import {mdiChevronDownCircleOutline, mdiChevronUpCircleOutline, mdiMagnify, mdiWindowClose} from "@mdi/js";
import axios from "axios";
import Push from "push.js";

export default function Chat() {
    const {recentMessages, receiver, messages, currentUserID} = usePage().props;
    // console.log('Receiver ID', receiver)
    // console.log('messages', messages)
    // console.log('recent', recentMessages)

    // Search Chat History
    const [isSearchActive, setIsSearchActive] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');

    const handleCloseSearch = () => {
        setIsSearchActive(false);
        setSearchTerm('');
    };

    // Highlight searched text
    const [highlightedIndices, setHighlightedIndices] = useState([]);
    const [currentHighlightedIndex, setCurrentHighlightedIndex] = useState(0);
    const handleNextHighlight = () => {
        setCurrentHighlightedIndex(prev => (prev + 1) % highlightedIndices.length);
    };

    const handlePrevHighlight = () => {
        setCurrentHighlightedIndex(prev => (prev - 1 + highlightedIndices.length) % highlightedIndices.length);
    };

    useEffect(() => {
        if (searchTerm && highlightedIndices.length > 0) {
            const currentIndex = highlightedIndices[currentHighlightedIndex];
            if (currentIndex !== undefined) {
                document.getElementById(`message-${currentIndex}`)
                    ?.scrollIntoView({behavior: "smooth", block: "nearest"});
            }
        }
    }, [searchTerm, highlightedIndices, currentHighlightedIndex]);

    const getCurrentDisplayIndex = () => {
        if (highlightedIndices.length === 0) return 0;
        return currentHighlightedIndex + 1;
    };


    // Focus the chat component
    const component = useRef(null);

    const scrollToBottom = () => {
        component.current?.scrollIntoView({behavior: "smooth"});
    };

    useEffect(() => {
        scrollToBottom();
    }, [recentMessages, receiver]);


    const [realTimeMessages, setRealTimeMessages] = useState([]);
    const [realTimeRecentMessages, setRealTimeRecentMessages] = useState([]);

    useEffect(() => {
        const db = getFirestore(firebaseApp);
        const messagesRef = collection(db, "messages");

        const unsubscribe = onSnapshot(messagesRef, (snapshot) => {
            // Firestore has detected a change, fetch the latest messages
            fetchMessagesFromBackend();
            fetchRecentMessagesFromBackend();
            read();
        });

        return () => unsubscribe(); // Clean up the listener
    }, [recentMessages,messages,receiver,currentUserID]);

    const fetchMessagesFromBackend = async () => {
        try {
            if (receiver[0] && receiver[0].firebase_uid) {
                const response = await axios.get('/api/chat/messages', {
                    params: {
                        receiverId: receiver[0].firebase_uid,
                        currentUserID: currentUserID
                    }
                });

                const newMessages = response.data;
                setRealTimeMessages(newMessages); // Update state with the new messages
            } else {
                console.log('No receiver ID to fetch messages for');
            }
        } catch (error) {
            console.error('Error fetching messages:', error);
        }
    };


    const fetchRecentMessagesFromBackend = async () => {
        try {
            const response = await axios.get('/api/chat/recent-chat', {
                params: {
                    currentUserID: currentUserID,
                }
            });

            const newMessages = response.data;
            setRealTimeRecentMessages(newMessages);
            newMessages.forEach(message => {
                if (message.read_at === null) {
                    Push.Permission.request(() => {
                        // Permission granted
                        Push.create(message.name, {
                            body: message.message,
                            silent: true,
                            onClick: function () {
                                window.focus();
                                this.close();
                            }
                        });
                    }, () => {
                        // Permission denied
                        console.log('User declined the notification permission.');
                    });
                }
            });
        } catch (error) {
            console.error('Error fetching messages:', error);
        }
    };

    const read = async () => {
        if (receiver[0] && receiver[0].firebase_uid) {
            try {
                const response = await axios.post('/markMessagesAsRead', {
                    receiverId: receiver[0].firebase_uid,
                    currentUserID: currentUserID
                });

                console.log(response.data);
            } catch (error) {
                console.error('Error in Axios POST request:', error);
            }
        } else {
            console.log('Nothing to read');
        }
    };

    return (
        <Card>
            <div className={'tw-border-4 tw-border-cyan-400 animate__animated animate__fadeIn'}>
                <div className="tw-flex tw-h-screen tw-bg-gray-200">
                    <Col className={'d-none d-lg-block'}>
                        <div className="tw-basis-2/6 tw-bg-white tw-border-r tw-overflow-hidden tw-h-screen">
                            <ChatSidebar recentMessages={realTimeRecentMessages} currentUserID={currentUserID}/>
                        </div>
                    </Col>

                    <div ref={component}></div>

                    <Col xs={12} lg={8} xl={9}>
                        <div className="tw-flex tw-flex-col tw-h-full tw-basis-4/6 tw-border-r">
                            {receiver[0]?.firebase_uid ? (
                                <>
                                    <ChatUserInfoHeader receiver={receiver} setIsSearchActive={setIsSearchActive} currentUserID={currentUserID}/>
                                    {isSearchActive && (
                                        <div className={`tw-relative tw-w-full`}>
                                            <Icon path={mdiMagnify} size={1}
                                                  className="tw-absolute tw-left-7 tw-top-1/2 tw-transform tw--translate-y-1/2"/>
                                            <input
                                                type="text"
                                                placeholder="Search messages..."
                                                value={searchTerm}
                                                onChange={(e) => setSearchTerm(e.target.value)}
                                                className="tw-w-full tw-pl-14 tw-py-2 tw-border-none focus:tw-outline-none"
                                            />
                                            <div
                                                className="tw-absolute tw-inset-y-0 tw-right-2 tw-pr-3 tw-flex tw-items-center text-sm leading-5"
                                            >
                                            <span className="tw-mr-2">
                                                {getCurrentDisplayIndex()} / {highlightedIndices.length}
                                            </span>
                                                <Icon path={mdiChevronUpCircleOutline} size={1}
                                                      onClick={handlePrevHighlight}
                                                      className={'tw-mr-0.5 tw-cursor-pointer'}/>
                                                <Icon path={mdiChevronDownCircleOutline} size={1}
                                                      onClick={handleNextHighlight}
                                                      className={'tw-mr-0.5 tw-cursor-pointer'}/>
                                                <Icon path={mdiWindowClose} size={1} className={'tw-cursor-pointer'}
                                                      onClick={handleCloseSearch}/>
                                            </div>
                                        </div>
                                    )}

                                    <div className="tw-messanger tw-sticky tw-overflow-y-auto tw-p-2 tw-mt-auto">
                                        <ChatMessages
                                            messages={realTimeMessages}
                                            receiverID={receiver[0].firebase_uid}
                                            searchTerm={searchTerm}
                                            currentHighlightedIndex={currentHighlightedIndex}
                                            setHighlightedIndices={setHighlightedIndices}
                                        />
                                    </div>

                                    <ChatInput receiver={receiver}/>

                                </>
                            ) : (
                                <div
                                    className="tw-flex tw-justify-center tw-items-center tw-text-center tw-bg-slate-100 tw-h-screen">
                                    <p className="tw-font-bold tw-text-3xl tw-text-gray-500">
                                        Please select a User to start
                                        chatting...
                                    </p>
                                </div>
                            )}
                        </div>
                    </Col>
                </div>
            </div>
        </Card>
    );
}
