import {
    mdiAccountCancel,
    mdiAccountCircle,
    mdiChat, mdiDelete, mdiDotsVertical,
    mdiInformation,
    mdiMagnify,
    mdiPhone,
    mdiTextBoxSearchOutline,
    mdiVideo
} from "@mdi/js";
import Icon from "@mdi/react";
import React, {useEffect, useState} from "react";
import {Dropdown, Image} from "react-bootstrap";
import {Link, router} from "@inertiajs/react";

export default function ChatUserInfoHeader({ receiver, setIsSearchActive }) {

    // Pass active status to chat to show search bar
    const showSearchBar = () => {
        setIsSearchActive(prev => !prev);
    };


    // Send to backend function
    const handleDeleteChat = async (receiverID) => {
        router.post(`/chat/delete`, {receiverID: receiverID});
    };

    const handleBlockUser = async (receiverID) => {
        router.post(`/user/block`, {receiverID: receiverID});
    };


    // Dropdown menu
    const [showDropdown, setShowDropdown] = useState(false);


    return (
        <div className="user-info-header tw-bg-white tw-px-5 tw-py-3 ">
            <div className="tw-flex tw-justify-between">
                <div className="tw-flex tw-items-center hover:tw-bg-gray-100 hover:tw-rounded-lg hover:tw-text-orange-700">
                    <div className={'tw-mr-2'}>
                        {receiver?.avatar !== undefined ? (
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
                    <Link
                        className="text-md tw-text-gray-500 tw-text-lg tw-no-underline "
                    >
                        {receiver[0]?.name}
                    </Link>
                </div>
                <div className={'tw-flex tw-items-center'}>
                    <Icon path={mdiInformation} size={1.2} className={'text-primary'}/>
                    <Icon path={mdiMagnify} size={1.2} className={'tw-ml-2 text-primary'} onClick={showSearchBar}/>
                    <Dropdown show={showDropdown} onToggle={() => setShowDropdown(!showDropdown)}>
                        <Dropdown.Toggle as={Icon} path={mdiDotsVertical} size={1} className="tw-ml-2 tw-cursor-pointer text-primary" />
                        <Dropdown.Menu>
                            <Dropdown.Header className={'border-bottom'}>Action</Dropdown.Header>
                            <Dropdown.Item onClick={() => handleDeleteChat(receiver.id)} className={'tw-flex tw-items-center'}>
                                <Icon path={mdiDelete} size={1} className={'tw-mr-0.5'}/>
                                Delete Chat</Dropdown.Item>
                            <Dropdown.Item onClick={() => handleBlockUser(receiver.id)} className={'tw-flex tw-items-center'}>
                                <Icon path={mdiAccountCancel} size={1} className={'tw-mr-0.5'}/>
                                Block User
                            </Dropdown.Item>
                        </Dropdown.Menu>
                    </Dropdown>
                </div>
            </div>
        </div>

    );
}
