import {Link, router} from "@inertiajs/react";
import {mdiAccountCircle, mdiChatPlusOutline, mdiMagnify} from "@mdi/js";
import Icon from "@mdi/react";
import {Button, Modal, Nav, Spinner} from "react-bootstrap";
import React, {useEffect, useState} from "react";
import axios from "axios";

export default function ChatSidebar({recentMessages, currentUserID}) {

    // Send to backend function
    const handleDeleteChat = async (receiverID) => {
        router.post(`/chat/delete`, {receiverID: receiverID});
    };

    const handleBlockUser = async (receiverID) => {
        router.post(`/user/block`, {receiverID: receiverID});
    };


    const [showModal, setShowModal] = useState(false);
    const [searchResults, setSearchResults] = useState([]);
    const [responseMessage, setResponseMessage] = useState('');

    function handleShowModal() {
        setShowModal(true);
    }

    function handleCloseModal() {
        setResponseMessage('');
        setShowModal(false);
        setSearchResults([]);
    }

    const handleSearchChange = async (value) => {
        if (value.length > 0) {
            const response = await fetch(`/search-users?q=${value}`);
            const data = await response.json();
            setSearchResults(data.users);
        } else {
            setSearchResults([]);
        }
    };

    // Search Recent Chat History
    const [searchTerm, setSearchTerm] = useState('');

    const handleSearchInputChange = (e) => {
        e.stopPropagation();
        setSearchTerm(e.target.value.toLowerCase());
    };

    const filteredMessages = searchTerm
        ? recentMessages.filter(
            message =>
                message.message.toLowerCase().includes(searchTerm) ||
                message.name.toLowerCase().includes(searchTerm) ||
                (message.message && message.message.toLowerCase().includes(searchTerm))
        )
        : recentMessages;


    // Count Unread Message
    const [count, setCount] = useState('');

    useEffect(() => {
        recentMessages.forEach(message => {
            unread(message.user_id);
        });
    }, [recentMessages]);

    const unread = async (receiverID) => {
        const response = await axios.post('/count-unreadMessage', {
            receiverID: receiverID,
            currentUserID: currentUserID
        });
        setCount(prevCounts => ({...prevCounts, [receiverID]: response.data.unreadCount}));
    }


    // Format the message length
    function formatMessage(message) {
        if (message && message.length > 15) {
            return message.substring(0, 15) + ".....";
        }
        return message;
    }


    // Right click show menu
    const [contextMenu, setContextMenu] = useState({
        mouseX: null,
        mouseY: null,
        user: null
    });
    const handleContextMenu = (event, user) => {
        event.preventDefault();
        setContextMenu({
            mouseX: event.clientX - 2,
            mouseY: event.clientY - 4,
            user
        });
    };
    const handleClose = () => {
        setContextMenu({mouseX: null, mouseY: null, user: null});
    };

    useEffect(() => {
        const handleClick = (event) => {
            if (contextMenu.mouseY !== null) {
                handleClose();
            }
        };

        document.addEventListener('click', handleClick);
        return () => {
            document.removeEventListener('click', handleClick);
        };
    }, [contextMenu]);



    const fakeContacts = [
        { name: "Alice Johnson", lastMessage: "Hey, how are you?" },
        { name: "Bob Smith", lastMessage: "Let's meet tomorrow!" },
        { name: "Charlie Davis", lastMessage: "Got the files, thanks!" },
        { name: "Alice Johnson", lastMessage: "Hey, how are you?" },
        { name: "Bob Smith", lastMessage: "Let's meet tomorrow!" },
        { name: "Charlie Davis", lastMessage: "Got the files, thanks!" },
        { name: "Alice Johnson", lastMessage: "Hey, how are you?" },
        { name: "Bob Smith", lastMessage: "Let's meet tomorrow!" },
        { name: "Charlie Davis", lastMessage: "Got the files, thanks!" },
        { name: "Alice Johnson", lastMessage: "Hey, how are you?" },
        { name: "Bob Smith", lastMessage: "Let's meet tomorrow!" },
        { name: "Charlie Davis", lastMessage: "Got the files, thanks!" },
        { name: "Alice Johnson", lastMessage: "Hey, how are you?" },
        { name: "Bob Smith", lastMessage: "Let's meet tomorrow!" },
        { name: "Charlie Davis", lastMessage: "Got the files, thanks!" },
        { name: "Alice Johnson", lastMessage: "Hey, how are you?" },
        { name: "Bob Smith", lastMessage: "Let's meet tomorrow!" },
        { name: "Charlie Davis", lastMessage: "Got the files, thanks!" },
        // Add more contacts as needed
    ];

    return (
        <div>
            <div className="search-box tw-text-slate-300 tw-p-2 tw-pt-3">
                <div className="tw-flex tw-justify-between tw-border-b tw-border-slate-100 tw-px-2 tw-pb-1">

                    <form className="tw-w-3/4">
                        <div className="tw-relative">
                            <Icon path={mdiMagnify} size={1}
                                  className="tw-absolute tw-left-3 tw-top-1/2 tw-transform tw--translate-y-1/2"/>
                            <input
                                type="search"
                                className="search tw-w-full tw-p-2 tw-pl-10 tw-rounded-full tw-bg-white tw-font-light tw-border-0 tw-hover:border-0 tw-focus:border-0 tw-focus:ring-0 tw-shadow-none tw-outline-none"
                                placeholder="Search"
                                onChange={handleSearchInputChange}
                            />
                        </div>
                    </form>

                    <div className="tw-flex tw-justify-center tw-items-center">
                        <Button
                            onClick={handleShowModal}
                        >
                            <Icon path={mdiChatPlusOutline} size={1}/>
                        </Button>
                    </div>
                </div>
            </div>


            <div className="user-list tw-overflow-y-auto tw-max-h-[calc(100vh-100px)]">
                {filteredMessages.length > 0 ? (
                    filteredMessages.map((user, index) => (
                        <div key={index}>
                            <Link
                                href={`/chat/${user.user_id}`}
                                onContextMenu={(e) => handleContextMenu(e, user)}
                                key={index}
                                className="tw-flex tw-px-6 tw-items-center tw-transition tw-hover:cursor-pointer hover:tw-bg-slate-100 tw-no-underline"
                            >
                                <div className="tw-pr-4">
                                    {user?.avatar !== undefined ? (
                                        <img
                                            src="https://cdn-icons-png.flaticon.com/512/194/194938.png"
                                            width="50"
                                        />
                                    ) : (
                                        <img
                                            src="https://cdn-icons-png.flaticon.com/512/194/194938.png"
                                            width="50"
                                        />
                                    )}
                                </div>

                                <div className="tw-flex tw-justify-between tw-items-center tw-mb-2 tw-w-full">
                                    <div className="tw-flex tw-flex-col tw-pt-6">
                                        <p className={`tw-text-black tw-mb-0 ${count[user.user_id] > 0 ? 'tw-font-bold' : ''}`}>
                                            {user.name.length > 0 ? user.name : "N/A"}
                                        </p>
                                        <p className={`tw-h-5 tw-overflow-hidden tw-text-sm tw-text-gray-400 ${count[user.user_id] > 0 ? 'tw-font-bold tw-text-gray-900' : ''}`}>
                                            {user.message && user.message.length > 0 ? formatMessage(user.message) : "No message"}
                                        </p>
                                    </div>
                                    <div className="tw-flex tw-flex-col tw-mt-2">
                                        {user.message_created_at || user.friend_created_at ? (
                                            <div
                                                className={`tw-text-xs tw-mb-0.5 ${count[user.user_id] > 0 ? 'tw-text-red-500' : 'tw-text-gray-400'}`}>
                                                {/*{formatTimestamp(user.message_created_at || user.friend_created_at)}*/}
                                                {user.formatted_timestamp}
                                            </div>
                                        ) : null}
                                        <span
                                            className={`bg-primary tw-text-white tw-px-2 tw-rounded-full tw-text-lg tw-ml-auto ${count[user.user_id] > 0 ? 'tw-w-6 tw-h-6 tw-flex tw-items-center tw-justify-center' : ''}`}>
                                        {count[user.user_id] || ''}
                                    </span>
                                    </div>
                                </div>
                            </Link>
                            {contextMenu.mouseY !== null && (
                                <div
                                    style={{
                                        position: 'absolute',
                                        top: `${contextMenu.mouseY}px`,
                                        left: `${contextMenu.mouseX}px`,
                                        zIndex: 1000
                                    }}
                                >
                                    <Dropdown.Menu show={true} onClose={handleClose}>
                                        <Dropdown.Header className={'border-bottom'}>Action</Dropdown.Header>
                                        <Dropdown.Item
                                            onClick={() => handleDeleteChat(user.user_id)}
                                            className={'tw-flex tw-items-center '}>
                                            <Icon path={mdiDelete} size={1} className={'tw-mr-0.5'}/>
                                            Delete Chat
                                        </Dropdown.Item>
                                        <Dropdown.Item
                                            onClick={() => handleBlockUser(user.user_id)}
                                            className={'tw-flex tw-items-center'}>
                                            <Icon path={mdiAccountCancel} size={1} className={'tw-mr-0.5'}/>
                                            Block User
                                        </Dropdown.Item>
                                    </Dropdown.Menu>

                                </div>
                            )}
                        </div>
                    ))
                ) : (
                    <div
                        className="tw-flex tw-justify-center tw-items-center tw-mt-52">
                        <p className="tw-font-bold tw-text-3xl tw-text-gray-500 tw-h-1/2">
                            Chat now!!!
                        </p>
                    </div>
                )}
            </div>


            {/* Modal */}
            <Modal
                show={showModal}
                onHide={handleCloseModal}
                centered
            >
                <Modal.Header closeButton><h3>Find Users</h3></Modal.Header>
                <Modal.Body className="text-center tw-overflow-hidden">
                    <input
                        type="text"
                        className="form-control modalSearch"
                        placeholder="Search for users"
                        onChange={(e) => handleSearchChange(e.target.value)}
                    />
                    {responseMessage &&
                        <div className="alert alert-info mt-3">
                            {responseMessage}
                        </div>
                    }

                    {searchResults.length > 0 ? (
                        <div>
                            {searchResults.map((user, index) => (
                                <div key={index}
                                     className="tw-flex tw-items-center tw-mb-2 tw-mt-2 tw-p-2"
                                     style={{
                                         border: '1px solid #e0e0e0',
                                         borderRadius: '8px',
                                         padding: '10px',
                                         boxShadow: '0 2px 4px rgba(0,0,0,0.1)',
                                         transition: 'box-shadow 0.3s ease-in-out',
                                         ':hover': {
                                             boxShadow: '0 4px 8px rgba(0,0,0,0.15)'
                                         }
                                     }}>

                                    {/* User Info and Clickable Area */}
                                    <div
                                        className="tw-flex tw-grow tw-items-center"
                                    >
                                        {user?.avatar !== undefined ? (
                                            <img
                                                src="https://cdn-icons-png.flaticon.com/512/194/194938.png"
                                                width="50"
                                            />
                                        ) : (
                                            <Icon path={mdiAccountCircle} size={2}/>
                                        )}
                                        <p className="tw-mb-0 tw-ml-2">{user.name}</p>
                                    </div>

                                    <Button
                                        as={'a'}
                                        variant={'outline-primary'}
                                        className={'tw-rounded-xl tw-transition-all hover:tw-drop-shadow-xl tw-flex tw-items-center tw-gap-x-2'}
                                        href={`/chat/${user.firebase_uid}`}
                                    >
                                        +
                                    </Button>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="tw-mt-3">
                            <h5 className={'text-muted'}>No user found.</h5>
                        </div>
                    )}
                </Modal.Body>
            </Modal>

        </div>
    );
}
